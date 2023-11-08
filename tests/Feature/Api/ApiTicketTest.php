<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Ticket;
use App\Models\TrashType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
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
    use DatabaseTransactions;
    use WithFaker;
    use WithoutMiddleware;


    /** @test */
    public function ticket_type_is_mandatory()
    {
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
    public function ticket_type_must_be_valid()
    {
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
    public function when_ticket_type_is_valid_it_returns_200()
    {
        $company = Company::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        foreach (['reservation', 'info', 'abandonment', 'report'] as $type) {
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
     * 
     * NOTA BENE:
     * IL FRONTEND PASSA LE COORDINATE SECONDO LO STANDARD LEAFLET [LAT,LNG] => [Y,X]
     * NEL DB SALVIAMO I POINT SECONDO LO STANDARD POSTGIS [LNG,LAT] => [X,Y]
     */

    /** @test */
    public function it_saves_proper_ticket_no_image()
    {
        // PREPARE
        $company = Company::factory()->create();
        $trash_type = TrashType::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create();
        $location = [42, 10];
        $note = $this->faker->sentence(100);
        $phone = $this->faker->phoneNumber();

        Sanctum::actingAs($user, ['*']);
        $data = [
            'trash_type_id' => $trash_type->id,
            'location' => $location,
            'note' => $note,
            'phone' => $phone,
        ];

        foreach (['reservation', 'info', 'abandonment', 'report'] as $type) {
            // SPECIFIC PREPARE
            $data['ticket_type'] = $type;

            // FIRE
            $response = $this->post("api/c/{$company->id}/ticket", $data);

            // CHECK RESPONSE CODE
            $this->assertSame(200, $response->status());

            // CHECK RESPONSE DATA (ticket object with id (no image))
            $j = $response->json();
            $data_out = $j['data'];

            $this->assertEquals($user->id, $data_out['user_id']);
            $this->assertEquals($trash_type->id, $data_out['trash_type_id']);
            $this->assertEquals((DB::select(DB::raw("SELECT ST_GeomFromText('POINT({$location[1]} {$location[0]})') as g;")))[0]->g, $data_out['geometry']);
            $this->assertEquals($note, $data_out['note']);
            $this->assertEquals($phone, $data_out['phone']);

            // CHECK DB DATA
            $ticket = Ticket::find($data_out['id']);
            $this->assertEquals($company->id, $ticket->company_id);
            $this->assertEquals($user->id, $ticket->user_id);
            $this->assertEquals($trash_type->id, $ticket->trash_type_id);
            $this->assertEquals($note, $ticket->note);
            $this->assertEquals($phone, $ticket->phone);

            $geojson = json_decode(DB::select(DB::raw("SELECT ST_AsGeoJson('{$ticket->geometry}') as g"))[0]->g);
            $this->assertEquals($location[0], $geojson->coordinates[1]);
            $this->assertEquals($location[1], $geojson->coordinates[0]);
        }
    }

    /** @test */
    public function it_saves_proper_image()
    {
        $image = 'data:image/png;base64,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';
        $company = Company::factory()->create();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->post("api/c/{$company->id}/ticket", [
            'ticket_type' => 'reservation',
            'image' => $image,
        ]);
        $this->assertSame(200, $response->status());

        // CHECK RESPONSE DATA (ticket object with id (no image))
        $j = $response->json();
        $data_out = $j['data'];
        $this->assertEquals($image, $data_out['image']);
        $ticket = Ticket::find($data_out['id']);
        $this->assertEquals($image, $ticket->image);
    }
}
