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
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
  use DatabaseTransactions;
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
}
