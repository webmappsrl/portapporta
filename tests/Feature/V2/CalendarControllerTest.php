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
        'unauthenticatedUser' => ''
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

    // Verificare che il calendario contenga i dati corretti
    private function verifyCalendarHasCorrectAddressesAndTrashTypes(AssertableJson $json): AssertableJson{
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

    // Verificare i dati dell'indirizzo
    private function verifyAddressData(AssertableJson $json): AssertableJson    {
        return $json->where('user_id', $this->address->user_id)
            ->where('zone_id', $this->address->zone_id)
            ->where('address', $this->address->address)
            ->has('location')
            ->where('user_type_id', $this->address->user_type_id)
            ->etc();
    }

    // Verificare i dati del calendario per il giorno successivo
    private function verifyThereIsTomorrowInCalendarData(AssertableJson $json): AssertableJson{
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        return $json->has($tomorrow, function ($json): void {
            $this->verifyCalendarItemDetails($json);
        });
    }   

    // Verificare i dettagli dell'elemento del calendario
    private function verifyCalendarItemDetails(AssertableJson $json): AssertableJson{
        return $json->has('0', function ($json) {
            $trashTypeJson = $json->has('trash_types', function ($json) {
                $this->verifyTrashTypeData($json);
            });
            $this->verifyCalendarItemSchedule($trashTypeJson);
        });
    }
    
    // Verificare i dati del tipo di rifiuto
    private function verifyTrashTypeData(AssertableJson $json): AssertableJson{
        return $json->has('0', function ($json) {
            $json->where('id', $this->trashType->id)
            ->where('name', $this->trashType->getTranslations('name'))
            ->where('description', $this->trashType->getTranslations('description'))
            ->etc();
        });
    }

    // Verificare il programma dell'elemento del calendario
    private function verifyCalendarItemSchedule(AssertableJson $json): AssertableJson{
        return $json
            ->where('start_time', $this->calendarItem->start_time)
            ->where('stop_time', $this->calendarItem->stop_time)
            ->where('frequency', $this->calendarItem->frequency);
    }
}