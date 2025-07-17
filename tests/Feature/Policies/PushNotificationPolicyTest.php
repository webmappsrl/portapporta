<?php

namespace Tests\Feature\Policies;

use App\Models\User;
use App\Models\Company;
use App\Models\PushNotification;
use App\Models\Zone;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;

class PushNotificationPolicyTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;

    private $company;
    private $contributor_user;
    private $admin_user;
    private $userZone;
    private $notification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = $this->createCompany();
        $this->userZone = $this->createZone($this->company);
        $this->contributor_user = $this->createUser($this->userZone, $this->company);
        $this->contributor_user->assignRole('contributor');
        $this->admin_user = $this->createUser($this->userZone, $this->company, [
            'admin_company_id' => $this->company->id,
        ]);
        $this->admin_user->assignRole('company_admin');
        PushNotification::unsetEventDispatcher();
        $this->notification = PushNotification::factory()->create([
            'company_id' => $this->company->id,
            'zone_ids' => [$this->userZone->id],
            'title' => 'Test notification title',
            'message' => 'Test notification message',
            'schedule_date' => now()->addDay(),
            'status' => 0,
        ]);
        PushNotification::setEventDispatcher(app('events'));
    }
    private function setNotificationScheduleToFuture(): void
    {
        $this->notification->schedule_date = now()->addDays(2);
        $this->notification->save();
    }

    private function setNotificationScheduleToPast(): void
    {
        $this->notification->schedule_date = now()->subDays(2);
        $this->notification->save();
    }

    /** @test */
    public function testAdminCanUpdateNotificationWithFutureSchedule()
    {
        $this->setNotificationScheduleToFuture();
        $this->assertTrue($this->admin_user->hasRole('company_admin'));
        $this->assertTrue($this->notification->schedule_date->isFuture());
        $this->assertTrue($this->admin_user->can('update', $this->notification));
    }

    /** @test */
    public function testAdminCannotUpdateNotificationWithPastSchedule()
    {
        $this->setNotificationScheduleToPast();
        $this->assertTrue($this->admin_user->hasRole('company_admin'));
        $this->assertTrue($this->notification->schedule_date->isPast());
        $this->assertFalse($this->admin_user->can('update', $this->notification));
    }

    /** @test */
    public function testNonAdminCannotUpdateNotificationEvenIfScheduleIsFuture()
    {
        $this->setNotificationScheduleToFuture();
        $this->assertTrue($this->contributor_user->hasRole('contributor'));
        $this->assertTrue($this->notification->schedule_date->isFuture());
        $this->assertFalse($this->contributor_user->can('update', $this->notification));
    }

    /** @test */
    public function testAdminCanDeleteNotificationWithFutureSchedule()
    {
        $this->setNotificationScheduleToFuture();
        $this->assertTrue($this->admin_user->hasRole('company_admin'));
        $this->assertTrue($this->notification->schedule_date->isFuture());
        $this->assertTrue($this->admin_user->can('delete', $this->notification));
    }

    /** @test */
    public function testAdminCannotDeleteNotificationWithPastSchedule()
    {
        $this->setNotificationScheduleToPast();
        $this->assertTrue($this->admin_user->hasRole('company_admin'));
        $this->assertTrue($this->notification->schedule_date->isPast());
        $this->assertFalse($this->admin_user->can('delete', $this->notification));
    }
    /** @test */
    public function testNonAdminCannotDeleteNotificationEvenIfScheduleIsFuture()
    {
        $this->setNotificationScheduleToFuture();
        $this->assertTrue($this->contributor_user->hasRole('contributor'));
        $this->assertTrue($this->notification->schedule_date->isFuture());
        $this->assertFalse($this->contributor_user->can('delete', $this->notification));
    }
}
