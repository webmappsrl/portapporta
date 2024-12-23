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
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;

class PushNotificationControllerTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;
    private string $baseUrl;
    private Company $company;
    private User $user;
    private User $fakeUser;
    private Zone $userZone;
    private Address $address;
    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->userZone = $this->createZone($this->company);
        $this->user = $this->createUser($this->userZone, $this->company);
        $this->fakeUser = $this->createUser();
        $this->address = $this->createAddress($this->user, $this->userZone);
        $this->baseUrl = "/api/v2/c/{$this->company->id}/pushnotification";
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

    private function assertThatHasNoData($response): void{
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('data', 0)
            ->etc()
        );
    }

    private function assertThatDataMatchesNotification($response, PushNotification $notification){
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('data', fn (AssertableJson $json) =>
                $json->whereType('0', 'array')
                    ->first(fn (AssertableJson $json) =>
                        $json->where('id', $notification->id)
                            ->where('title', $notification->title)
                            ->where('message', $notification->message)
                            ->where('status', $notification->status)
                            ->etc()
                    )->etc()
            )->etc()
        );
    }


    /** @test */
    public function testUnauthorizedAccessDenied()
    {
        $response = $this->get($this->baseUrl);
        $this->assertSame(403, $response->status());
    }

    /** @test */
    public function testEmptyNotificationsForUser()
    {
        Sanctum::actingAs($this->fakeUser, ['*']);

        $response = $this->get($this->baseUrl);
        $this->assertSuccessResponse($response, "Push notification list.");
        $this->assertThatHasNoData($response);
    }

    /** @test */
    public function testPushNotificationListReturnsSuccess()
    {

        Sanctum::actingAs($this->user, ['*']);
        $notification = $this->createPushNotification($this->userZone);

        $response = $this->get($this->baseUrl);

        $this->assertSuccessResponse($response, "Push notification list.");
        $this->assertThatDataMatchesNotification($response, $notification);
    }

    /** @test */
    public function testPushNotificationListReturnsEmptyForOldNotifications()
    {
        Sanctum::actingAs($this->user, ['*']);
        $this->createPushNotification($this->userZone, false);

        $response = $this->get($this->baseUrl);
        $this->assertSuccessResponse($response, "Push notification list.");
        $this->assertThatHasNoData($response);
    }

    /** @test */
    public function testPushNotificationNotVisibleForDifferentZone()
    {
        $notificationZone = $this->createZone($this->company);
        Sanctum::actingAs($this->user, ['*']);
        $this->createPushNotification($notificationZone);

        $response = $this->get($this->baseUrl);
        $this->assertSuccessResponse($response, "Push notification list.");
        $this->assertThatHasNoData($response);
    }
}
