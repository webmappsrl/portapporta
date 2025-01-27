<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Calendar;
use App\Models\Zone;
use App\Models\UserType;
use App\Models\Company;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use App\Models\CalendarItem;
use App\Models\TrashType;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;
class CalendarControllerTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;
    private $user;
    private $address;
    private $calendar;
    private $zone;
    private $userType;
    private $company;
    private $otherCompany;
    private $trashType;
    private $calendarItem;
    const API_PREFIX = '/api/v2/c/';
    const responseMessages = [
        'calendarCreated' => 'Calendar created.',
        'companyHasNoCalendars' => 'Company has no calendars.',
        'datesAreNotValid' => 'The dates are not valid.',
        'unauthenticatedUser' => '',
        'companyNotFound' => 'Company not found.',
        'zoneNotFound' => 'Zone not found.',
        'noCalendarsForZone' => 'No calendars found for the specified zone.',
        'calendarsRetrievedSuccessfully' => 'Calendars retrieved successfully.',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->zone = Zone::factory()->create();
        $this->userType = UserType::factory()->create();
        $this->company = Company::factory()->create();
        $this->otherCompany = Company::factory()->create();
        $this->trashType = TrashType::factory()->create();
        $this->address = $this->createAddress($this->user, $this->zone, ['user_type_id' => $this->userType->id]);
        $this->calendar = Calendar::factory()->create([
            'zone_id' => $this->address->zone_id,
            'company_id' => $this->company->id,
            'user_type_id' => $this->address->user_type_id,
            'start_date' => Carbon::today()->subDays(5),
            'stop_date' => Carbon::today()->addDays(30)
        ]);
        $this->calendarItem = CalendarItem::factory()->create([
            'calendar_id' => $this->calendar->id,
            'day_of_week' => Carbon::tomorrow()->dayOfWeek,
            'start_time' => '08:00',
            'stop_time' => '12:00',
            'frequency' => 'weekly',
        ]);
        $this->calendarItem->trashTypes()->attach($this->trashType->id);
    }

    /** @test */
    public function testV1Index()
    {
        Sanctum::actingAs($this->user);
        $response = $this->get(self::API_PREFIX . $this->company->id . '/calendar');
        $this->assertSuccessResponse(
            $response,
            self::responseMessages['calendarCreated']
        );
        $response->assertJson(fn (AssertableJson $json) =>
            $this->verifyCalendarHasCorrectAddressesAndTrashTypes($json)
            ->etc()
        );
    }

    /** @test */
    public function testV1IndexNoCalendarsReturnsError()
    {
        Sanctum::actingAs($this->user);

        $this->assertErrorResponse(
            $this->get(self::API_PREFIX . $this->otherCompany->id . '/calendar'),
            self::responseMessages['companyHasNoCalendars'],
            400
        );
    }

    /** @test */
    public function testV1IndexInvalidDatesReturnsError()
    {
        Sanctum::actingAs($this->user);

        $this->assertErrorResponse(
            $this->get(self::API_PREFIX . $this->company->id . '/calendar?start_date=2023-12-31&stop_date=2023-12-01'),
            self::responseMessages['datesAreNotValid'],
            400
        );
    }
    
    /** @test */
    public function testV1IndexUnauthenticatedUserReturnsError()
    {
        $this->assertErrorResponse(
            $this->get(self::API_PREFIX . $this->company->id . '/calendar'),
            self::responseMessages['unauthenticatedUser'],
            403
        );
    }

    // --------------------------------------------
    // Tests for V1IndexByZone Function
    // --------------------------------------------

    /** @test */
    public function testV1IndexByZoneCompanyDoesNotExist()
    {
        Sanctum::actingAs($this->user);
        $nonExistentCompanyId = 999999; // Assuming this ID does not exist
        $zoneId = $this->zone->id;

        $response = $this->get(self::API_PREFIX . "{$nonExistentCompanyId}/calendar/z/{$zoneId}");

        $this->assertErrorResponse(
            $response,
            self::responseMessages['companyNotFound'],
            400
        );
    }

    /** @test */
    public function testV1IndexByZoneZoneDoesNotExist()
    {
        Sanctum::actingAs($this->user);
        $companyId = $this->company->id;
        $nonExistentZoneId = 999999; // Assuming this ID does not exist

        $response = $this->get(self::API_PREFIX . "{$companyId}/calendar/z/{$nonExistentZoneId}");

        $this->assertErrorResponse(
            $response,
            self::responseMessages['zoneNotFound'],
            400
        );
    }

    /** @test */
    public function testV1IndexByZoneUnauthenticatedUserReturnsError()
    {
        $companyId = $this->company->id;
        $zoneId = $this->zone->id;

        $response = $this->get(self::API_PREFIX . "{$companyId}/calendar/z/{$zoneId}");

        $this->assertErrorResponse(
            $response,
            self::responseMessages['unauthenticatedUser'],
            403
        );
    }

    /** @test */
    public function testV1IndexByZoneInvalidDatesReturnsError()
    {
        Sanctum::actingAs($this->user);
        $companyId = $this->company->id;
        $zoneId = $this->zone->id;

        // start_date is after stop_date
        $response = $this->get(self::API_PREFIX . "{$companyId}/calendar/z/{$zoneId}?start_date=2025-01-31&stop_date=2025-01-01");

        $this->assertErrorResponse(
            $response,
            self::responseMessages['datesAreNotValid'],
            400
        );
    }

    /** @test */
    public function testV1IndexByZoneSuccessWithoutDates()
    {
        Sanctum::actingAs($this->user);
        $companyId = $this->company->id;
        $zoneId = $this->zone->id;

        $response = $this->get(self::API_PREFIX . "{$companyId}/calendar/z/{$zoneId}");

        $this->assertSuccessResponse(
            $response,
            self::responseMessages['calendarsRetrievedSuccessfully']
        );

        $response->assertJson(fn (AssertableJson $json) =>
            $this->verifyV1IndexByZoneResponseData($json)
            ->etc()
        );
    }

    /** @test */
    public function testV1IndexByZoneSuccessWithDates()
    {
        Sanctum::actingAs($this->user);
        $companyId = $this->company->id;
        $zoneId = $this->zone->id;
        $startDate = Carbon::tomorrow()->format('Y-m-d');
        $stopDate = Carbon::tomorrow()->addDays(14)->format('Y-m-d');

        $response = $this->get(self::API_PREFIX . "{$companyId}/calendar/z/{$zoneId}?start_date={$startDate}&stop_date={$stopDate}");

        $this->assertSuccessResponse(
            $response,
            self::responseMessages['calendarsRetrievedSuccessfully']
        );

        $response->assertJson(fn (AssertableJson $json) =>
            $this->verifyV1IndexByZoneResponseData($json)
            ->etc()
        );
    }

    // --------------------------------------------
    // Helper Methods for V1IndexByZone Tests
    // --------------------------------------------

    /**
     * Verify the response data structure and content for V1IndexByZone.
     *
     * @param AssertableJson $json
     * @return AssertableJson
     */
    private function verifyV1IndexByZoneResponseData(AssertableJson $json): AssertableJson
    {
        return $json->has('data', function ($json) {
            $json->has('zone', function ($json) {
                $this->verifyZoneData($json);
            })
            ->has('company', function ($json) {
                $this->verifyCompanyData($json);
            })
            ->has('calendar', function ($json) {
                $this->verifyThereIsTomorrowInCalendarData($json)
                ->etc();
            });
        })
        ->where('message', self::responseMessages['calendarsRetrievedSuccessfully'])
        ->etc();
    }

    /**
     * Verify the zone data in the response.
     *
     * @param AssertableJson $json
     * @return AssertableJson
     */
    private function verifyZoneData(AssertableJson $json): AssertableJson
    {
        return $json->where('id', $this->zone->id)
                    ->etc();
    }

    /**
     * Verify the company data in the response.
     *
     * @param AssertableJson $json
     * @return AssertableJson
     */
    private function verifyCompanyData(AssertableJson $json): AssertableJson
    {
        return $json->where('id', $this->company->id)
                    ->where('name', $this->company->name)
                    ->etc();
    }    

    /**
     * Verify the trash type data in the response.
     *
     * @param AssertableJson $json
     * @return AssertableJson
     */
    private function verifyTrashTypeData(AssertableJson $json): AssertableJson
    {
        return $json->has('0', function ($json) {
            $json->where('id', $this->trashType->id)
                 ->where('name', $this->trashType->getTranslations('name'))
                 ->where('description', $this->trashType->getTranslations('description'))
                 ->etc();
        });
    }

    // --------------------------------------------
    // Existing Helper Methods
    // --------------------------------------------

    /**
     * Verify that the calendar has the correct addresses and trash types.
     *
     * @param AssertableJson $json
     * @return AssertableJson
     */
    private function verifyCalendarHasCorrectAddressesAndTrashTypes(AssertableJson $json): AssertableJson
    {
        return $json->has('data.0', function ($json) {
            $json->has('address', function ($json) {
                $this->verifyAddressData($json);
            })
            ->has('calendar', function ($json) {
                $this->verifyThereIsTomorrowInCalendarData($json)
                ->etc(); // Ignora i dati di altri giorni per il test
            });
        });
    }

    /**
     * Verify the address data in the response.
     *
     * @param AssertableJson $json
     * @return AssertableJson
     */
    private function verifyAddressData(AssertableJson $json): AssertableJson
    {
        return $this->assertAddressData($json, $this->createFieldsToCheckForAddress($this->address));
    }

    /**
     * Verify that tomorrow's date is present in the calendar data.
     *
     * @param AssertableJson $json
     * @return AssertableJson
     */
    private function verifyThereIsTomorrowInCalendarData(AssertableJson $json): AssertableJson
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        return $json->has($tomorrow, function ($json): void {
            $this->verifyCalendarItemDetails($json);
        });
    }

    /**
     * Verify the details of a calendar item.
     *
     * @param AssertableJson $json
     * @return AssertableJson
     */
    private function verifyCalendarItemDetails(AssertableJson $json): AssertableJson
    {
        return $json->has('0', function ($json) {
            $json->has('trash_types', function ($json) {
                $this->verifyTrashTypeData($json);
            });
            $this->verifyCalendarItemSchedule($json);
        });
    }

    /**
     * Verify the schedule details of a calendar item.
     *
     * @param AssertableJson $json
     * @return AssertableJson
     */
    private function verifyCalendarItemSchedule(AssertableJson $json): AssertableJson
    {
        return $json->where('start_time', $this->calendarItem->start_time)
                    ->where('stop_time', $this->calendarItem->stop_time)
                    ->where('frequency', $this->calendarItem->frequency);
    }
}