<?php

namespace Tests\Feature;

use App\Models\Calendar;
use App\Models\CalendarItem;
use App\Models\Company;
use App\Models\TrashType;
use App\Models\User;
use App\Models\UserType;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * https://portapporta.webmapp.it/api/c/{company_id}/calendar
 * 
 * Restituisce il calendario dell'utente loggato (Zona + UserType) con i successivi 15 elementi
 * con il seguente formato
 *     {
	  '2022-06-03' : [
	    {
		  'trash_types' : [id1,id2,...,idn],
		  'start_time' : '07:00',
		  'stop_time' : '13:00'
		},
	    {
		  'trash_types' : [idA1,idA2,...,idAn],
		  'start_time' : '04:00',
		  'stop_time' : '19:00'
		}
	  ],
	  '2022-06-03' : [
	    {
		  'trash_types' : [id1,id2,...,idn],
		  'start_time' : '07:00',
		  'stop_time' : '13:00'
		},
	    {
 * 
 */
class ApiCalendarTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /** @test */
    public function user_must_be_authenticated()
    {
        $company = Company::factory()->create();
        $response = $this->get("api/c/{$company->id}/calendar", [
            'ticket_type' => 'reservation',
        ]);
        $this->assertSame(403, $response->status());
    }

    /** @test */
    public function when_user_has_no_zone_then_it_sends_error()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['zone_id' => null]);
        Sanctum::actingAs($user, ['*']);
        $response = $this->get("api/c/{$company->id}/calendar", [
            'ticket_type' => 'reservation',
        ]);
        $this->assertSame(400, $response->status());
        $this->assertSame('{"success":false,"message":"User has no zones."}', $response->content());
    }

    /** @test */
    public function when_user_has_no_user_type_then_it_sends_error()
    {
        $company = Company::factory()->create();
        $zone = Zone::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['zone_id' => $zone->id]);
        Sanctum::actingAs($user, ['*']);
        $response = $this->get("api/c/{$company->id}/calendar", [
            'ticket_type' => 'reservation',
        ]);
        $this->assertSame(400, $response->status());
        $this->assertSame('{"success":false,"message":"User has no user types."}', $response->content());
    }
    /** @test */
    public function when_company_has_no_calendar_it_sends_error()
    {
        $company = Company::factory()->create();
        $zone = Zone::factory()->create(['company_id' => $company->id]);
        $user_type = UserType::factory()->create(['company_id' => $company->id]);
        $user = User::factory()
            ->create([
                'zone_id' => $zone->id,
                'user_type_id' => $user_type->id
            ]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->get("api/c/{$company->id}/calendar", [
            'ticket_type' => 'reservation',
        ]);

        $this->assertSame(400, $response->status());
        $this->assertSame('{"success":false,"message":"Company has no calendars."}', $response->content());
    }

    /** @test */
    public function when_no_calendars_match_it_sends_error()
    {
        $company = Company::factory()->create();
        $zone = Zone::factory()->create(['company_id' => $company->id]);
        $user_type = UserType::factory()->create(['company_id' => $company->id]);
        $calendar = Calendar::factory()->create([
            'company_id' => $company->id,
            'zone_id' => $zone->id,
            'user_type_id' => $user_type->id,
            'start_date' => Carbon::parse('today - 12 months'),
            'stop_date' => Carbon::parse('today - 6 months'),
        ]);

        $user = User::factory()
            ->create([
                'zone_id' => $zone->id,
                'user_type_id' => $user_type->id
            ]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->get("api/c/{$company->id}/calendar", [
            'ticket_type' => 'reservation',
        ]);

        $this->assertSame(400, $response->status());
        $contentResponse = json_decode($response->content());
        $this->assertSame(false, $contentResponse->success);
        $this->assertSame("No calendars matching.", $contentResponse->message);
    }

    /** @test */
    public function when_parameters_are_ok_it_returns_200()
    {
        $this->prepareTest();
        $this->assertSame(200, $this->response->status());
    }

    /** @test */
    public function when_parameters_are_ok_it_returns_proper_response()
    {
        $this->prepareTest();
        $this->assertSame(200, $this->response->status());
        $j = $this->response->json();
        $this->assertArrayHasKey('data', $j);
        $this->assertIsArray($j['data']);
    }
    /**
     * API ITEM BLOCK
     * 
     * 	  '2022-06-03' : [
	    {
		  'trash_types' : [id1,id2,...,idn],
		  'start_time' : '07:00',
		  'stop_time' : '13:00'
		}, 
     * @test */
    public function when_parameters_are_ok_it_returns_proper_dates()
    {
        $this->prepareTest();
        $this->assertSame(200, $this->response->status());
        $j = $this->response->json();
        $this->assertArrayHasKey('data', $j);
        $this->assertIsArray($j['data']);
        $data = $j['data'];
        $this->assertEquals(14, count($data));
        for ($i = 0; $i < 14; $i++) {
            $this->assertArrayHasKey(Carbon::parse("today + $i days")->format('Y-m-d'), $data);
        }
    }

    /** @test */
    public function when_parameters_are_ok_it_returns_proper_item()
    {
        $this->prepareTest();
        $this->assertSame(200, $this->response->status());
        $j = $this->response->json();
        $this->assertArrayHasKey('data', $j);
        $this->assertIsArray($j['data']);
        $data = $j['data'];
        foreach ($data as $date => $items) {
            $this->assertIsArray($items);
            $this->assertEquals(1, count($items));
            $item = $items[0];
            $this->assertArrayHasKey('trash_types', $item);
            $this->assertArrayHasKey('start_time', $item);
            $this->assertArrayHasKey('stop_time', $item);
            // $this->assertContains($this->trash_type_1->id,$item['trash_types']);
            // $this->assertContains($this->trash_type_2->id,$item['trash_types']);
            // $this->assertContains($this->trash_type_3->id,$item['trash_types']);
            $this->assertEquals('07:00', $item['start_time']);
            $this->assertEquals('13:00', $item['stop_time']);
        }
    }

    private function prepareTest()
    {
        $this->company = Company::factory()->create();
        $this->zone = Zone::factory()->create(['company_id' => $this->company->id]);
        $this->user_type = UserType::factory()->create(['company_id' => $this->company->id]);
        $this->calendar = Calendar::factory()
            ->create([
                'company_id' => $this->company->id,
                'zone_id' => $this->zone->id,
                'user_type_id' => $this->user_type->id,
                'start_date' => Carbon::parse('today - 3 months'),
                'stop_date' => Carbon::parse('today + 3 months'),
            ]);

        $this->trash_type_1 = TrashType::factory()->create(['company_id' => $this->company->id]);
        $this->trash_type_2 = TrashType::factory()->create(['company_id' => $this->company->id]);
        $this->trash_type_3 = TrashType::factory()->create(['company_id' => $this->company->id]);

        $item_data = [
            'calendar_id' => $this->calendar->id,
            'start_time' => '7:00',
            'stop_time' => '13:00',
            'frequency' => 'weekly'
        ];

        for ($i = 0; $i < 7; $i++) {
            $item_data['day_of_week'] = $i;
            $item = CalendarItem::factory()->create($item_data);
            $item->trashTypes()->sync([$this->trash_type_1->id, $this->trash_type_2->id, $this->trash_type_3->id]);
        }

        $this->user = User::factory()
            ->create([
                'zone_id' => $this->zone->id,
                'user_type_id' => $this->user_type->id
            ]);

        Sanctum::actingAs($this->user, ['*']);

        $this->response = $this->get("api/c/{$this->company->id}/calendar", [
            'ticket_type' => 'reservation',
        ]);
    }
}
