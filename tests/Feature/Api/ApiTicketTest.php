<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Ticket;
use App\Models\TrashType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
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
    use RefreshDatabase;
    use WithFaker;

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

    /** @test */
    public function when_ticket_type_is_valid_it_returns_200() {
        $company = Company::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        foreach(['reservation','info','abandonment','report' ] as $type ) {
            $response = $this->post("api/c/{$company->id}/ticket", [
                'ticket_type' => $type,
            ]);
            $this->assertSame(200, $response->status());    
        }

    }

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

    /** @test */
    public function it_saves_proper_ticket_no_image() {
        // PREPARE
        $company = Company::factory()->create();
        $trash_type = TrashType::factory()->create(['company_id'=>$company->id]);
        $user = User::factory()->create();
        $location = [$this->faker->longitude(),$this->faker->latitude()];
        $note = $this->faker->sentence(100);
        $phone = $this->faker->phoneNumber();

        Sanctum::actingAs($user,['*']);
        $data = [
            'trash_type_id' => $trash_type->id,
            'location' => $location,
            'note' => $note,
            'phone' => $phone,
        ];

        foreach(['reservation','info','abandonment','report' ] as $type ) {
            // SPECIFIC PREPARE
            $data['ticket_type']=$type;

            // FIRE
            $response = $this->post("api/c/{$company->id}/ticket", $data);

            // CHECK RESPONSE CODE
            $this->assertSame(200, $response->status());
            
            // CHECK RESPONSE DATA (ticket object with id (no image))
            $j = $response->json();
            $data_out = $j['data'];

            $this->assertEquals($user->id,$data_out['user_id']);
            $this->assertEquals($trash_type->id,$data_out['trash_type_id']);
            $this->assertEquals(DB::select(DB::raw("SELECT ST_GeomFromText('POINT({$location[0]} {$location[1]})') as g;"))[0]->g,$data_out['geometry']);
            $this->assertEquals($note,$data_out['note']);
            $this->assertEquals($phone,$data_out['phone']);
            
            // CHECK DB DATA
            $ticket = Ticket::find($data_out['id']);
            $this->assertEquals($company->id,$ticket->company_id);
            $this->assertEquals($user->id,$ticket->user_id);
            $this->assertEquals($trash_type->id,$ticket->trash_type_id);
            // TODO: checkit via geoojson ora WKT $this->assertEquals(DB::select(DB::raw("SELECT ST_GeomFromText('POINT({$location[0]} {$location[1]})') as g;"))[0]->g,$ticket->geometry);
            $this->assertEquals($note,$ticket->note);
            $this->assertEquals($phone,$ticket->phone);

        }

    }

    public function it_saves_proper_image() {}
}
