<?php

namespace Tests\Feature\V2;

use App\Models\User;
use App\Models\Address;
use App\Models\Company;
use App\Models\PushNotification;
use App\Models\Zone;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class PushNotificationControllerTest extends TestCase
{
    use DatabaseTransactions;

    private string $baseUrl;
    private Company $company;
    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->baseUrl = "/api/v2/c/{$this->company->id}/pushnotification";
    }

    private function createAuthenticatedUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    private function createUserWithZone(User $user, Zone $zone): void
    {
        $user->companyWhereAdmin()->associate($this->company);
        $user->zone_id = $zone->id;
        $user->save();
    }

    private function createAddress(User $user, Zone $zone): Address
    {
        return Address::factory()->create([
            'user_id' => $user->id,
            'zone_id' => $zone->id,
            'address' => '123 Test Street'
        ]);
    }

    private function createPushNotification(Zone $zone, bool $isRecent = true): PushNotification
    {
        PushNotification::unsetEventDispatcher();
        $notification =  PushNotification::factory()->create([
            'company_id' => $this->company->id,
            'status' => true,
            'zone_ids' => [$zone->id],
            'created_at' => $isRecent ? now()->subDays(2) : now()->subDays(50),
            'title' => 'Test Notification Title',
            'message' => 'Test Notification Message'
        ]);
        PushNotification::setEventDispatcher(app('events'));
        return $notification;
    }

    public function testUnauthorizedAccessDenied()
    {
        $response = $this->get($this->baseUrl);
        $this->assertSame(403, $response->status());
    }

    public function testEmptyNotificationsForUser()
    {
        $this->createAuthenticatedUser();

        $response = $this->get($this->baseUrl);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('success')
                    ->where('success', true)
                    ->has('data', 0)
                    ->etc()
            );
    }

    public function testPushNotificationListReturnsSuccess()
    {
        $user = User::factory()->create();
        $zone = Zone::factory()->create(['company_id' => $this->company->id]);

        $this->createUserWithZone($user, $zone);
        Sanctum::actingAs($user, ['*']);
        $this->createAddress($user, $zone);
        $notification = $this->createPushNotification($zone);

        $response = $this->get($this->baseUrl);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('success')
                    ->where('success', true)
                    ->has('data', fn (AssertableJson $json) =>
                        $json->whereType('0', 'array')
                            ->first(fn (AssertableJson $json) =>
                                $json->where('id', $notification->id)
                                    ->where('title', $notification->title)
                                    ->where('message', $notification->message)
                                    ->where('status', $notification->status)
                                    ->etc()
                            )
                    )
                    ->etc()
            );
    }

    public function testPushNotificationListReturnsEmptyForOldNotifications()
    {
        $user = User::factory()->create();
        $zone = Zone::factory()->create(['company_id' => $this->company->id]);

        $this->createUserWithZone($user, $zone);
        Sanctum::actingAs($user, ['*']);
        $this->createAddress($user, $zone);
        $this->createPushNotification($zone, false);

        $response = $this->get($this->baseUrl);

        $response->assertOk()
                 ->assertJson(fn (AssertableJson $json) =>
                     $json->has('success')
                          ->where('success', true)
                          ->has('data', 0)
                          ->etc()
                 );
    }

    public function testPushNotificationNotVisibleForDifferentZone()
    {
        $user = User::factory()->create();
        $userZone = Zone::factory()->create(['company_id' => $this->company->id]);
        $notificationZone = Zone::factory()->create(['company_id' => $this->company->id]);

        $this->createUserWithZone($user, $userZone);
        Sanctum::actingAs($user, ['*']);
        $this->createAddress($user, $userZone);
        $this->createPushNotification($notificationZone);

        $response = $this->get($this->baseUrl);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('success')
                    ->where('success', true)
                    ->has('data', 0)
                    ->etc()
            );
    }

}
