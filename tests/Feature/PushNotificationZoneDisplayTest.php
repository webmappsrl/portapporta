<?php

namespace Tests\Feature;

use App\Models\PushNotification;
use Tests\TestCase;
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PushNotificationZoneDisplayTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;

    private $company;
    private $contributor_user;
    private $admin_user;
    private $userZone1;
    private $userZone2;
    private $notification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = $this->createCompany();
        $this->userZone1 = $this->createZone($this->company);
        $this->userZone2 = $this->createZone($this->company);
        $this->admin_user = $this->createUser($this->userZone1, $this->company, [
            'admin_company_id' => $this->company->id,
        ]);
        $this->admin_user->assignRole('company_admin');
        PushNotification::unsetEventDispatcher();
        $this->notification = PushNotification::factory()->create([
            'company_id' => $this->company->id,
            'zone_ids' => [$this->userZone1->id, $this->userZone2->id],
            'title' => 'Test Notification',
            'message' => 'This is a test message',
        ]);
        PushNotification::setEventDispatcher(app('events'));
    }

    /** @test */
    public function it_displays_all_zones_when_all_zones_are_selected()
    {
        $this->actingAs($this->admin_user);
        $zones = $this->admin_user->companyWhereAdmin->zones->pluck('id')->toArray();
        $this->notification->zone_ids = $zones;
        $this->notification->save();
        $response = $this->getJson('/nova-api/push-notifications');
        $response->assertStatus(200);
        $this->assertFalse(str_contains($response->getContent(), $this->userZone1->label));
        $this->assertTrue(str_contains($response->getContent(), __('All zones')));
    }

    /** @test */
    public function it_does_not_display_all_zones_when_only_some_are_selected()
    {
        $this->actingAs($this->admin_user);
        $this->notification->zone_ids = [$this->userZone1->id];
        $this->notification->save();
        $response = $this->getJson('/nova-api/push-notifications');
        $response->assertStatus(200);
        $this->assertFalse(str_contains($response->getContent(), __('All zones')));
        $this->assertTrue(str_contains($response->getContent(), $this->userZone1->label));
    }
}
