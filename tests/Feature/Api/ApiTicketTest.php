<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;



/**
 * con il seguente datamodel
 * interface Ticket {
 * ticket_type: 'reservation' || 'info' || 'abandonment' || 'report' 
 * trash_type_id:int; (FK recuperato da http://portapporta.webmapp.it/api/c/4/trash_types.json)
 * user_id: int; (FK id dell'utente loggato)
 * location:[int,int](long,lat)
 * image?: string;  (base64)
 * note?: string;
 * phone?:string
 * }
 */
class ApiTicketTest extends TestCase
{
    /** @test */
    public function user_must_be_authenticated() {
        $company = Company::factory()->create();
        $response = $this->post("api/c/{$company->id}/ticket", [
            'ticket_type' => 'reservation',
        ]);
        $this->assertSame(403, $response->status());
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->post("api/c/{$company->id}/ticket", [
            'ticket_type' => 'reservation',
        ]);
        $this->assertSame(200, $response->status());
    }

    /** @test */
    public function ticket_type_is_mandatory() {
        $company = Company::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->post("api/c/{$company->id}/ticket", []);
        $this->assertSame(400, $response->status());
        $this->assertSame('{"success":false,"message":"The ticket type field is required."}', $response->content());

        $response = $this->post("api/c/{$company->id}/ticket", [
            'ticket_type' => 'reservation',
        ]);
        $this->assertSame(200, $response->status());


    }

    /** @test */
    public function ticket_type_must_be_valid() {
        $company = Company::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->post("api/c/{$company->id}/ticket", [
            'ticket_type' => 'xxx',
        ]);
        $this->assertSame(400, $response->status());
        $this->assertSame('{"success":false,"message":"The selected ticket type is invalid."}', $response->content());

    }

    public function when_ticket_type_is_valid_it_returns_200() {}

    public function it_saves_proper_ticket_no_image() {}

    public function it_saves_proper_image() {}
}
