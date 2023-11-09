<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API:Route::get('/{id}/tickets', [TicketController::class, 'list'])->name('ticket.list');
 */
class ApiTicketListTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /** @test */
    public function user_must_be_authenticated()
    {
        $company = Company::factory()->create();

        $response = $this->get("api/c/{$company->id}/tickets", []);
        $this->assertSame(403, $response->status());

        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
        $response = $this->get("api/c/{$company->id}/tickets", []);
        $this->assertSame(200, $response->status());
    }

    /** @test */
    public function when_user_has_no_tickets_it_returns_empty_array()
    {
        $company = Company::factory()->create();

        $response = $this->get("api/c/{$company->id}/tickets", []);
        $this->assertSame(403, $response->status());

        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );

        $response = $this->get("api/c/{$company->id}/tickets", []);
        $this->assertSame(200, $response->status());
        $j = $response->json();
        $this->assertArrayHasKey('data', $j);
        $this->assertIsArray($j['data']);
        $data = $j['data'];
        $this->assertEquals(0, count($data));
    }

    /** @test */
    public function when_user_has_tickets_it_returns_proper_array()
    {
        $company = Company::factory()->create();

        $response = $this->get("api/c/{$company->id}/tickets", []);
        $this->assertSame(403, $response->status());

        $user = User::factory()->create();
        Ticket::factory(10)->create(['user_id' => $user->id, 'company_id' => $company->id]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->get("api/c/{$company->id}/tickets", []);
        $this->assertSame(200, $response->status());
        $j = $response->json();
        $this->assertArrayHasKey('data', $j);
        $this->assertIsArray($j['data']);
        $data = $j['data'];
        $this->assertEquals(10, count($data));
        foreach ($data as $ticket) {
            $this->assertIsArray($ticket);
            $this->assertArrayHasKey('user_id', $ticket);
            $this->assertArrayHasKey('company_id', $ticket);
            $this->assertArrayHasKey('trash_type_id', $ticket);
            $this->assertArrayHasKey('note', $ticket);
            $this->assertArrayHasKey('phone', $ticket);
            $this->assertArrayHasKey('location_address', $ticket);
            $this->assertArrayHasKey('image', $ticket);
        }
    }

    /** @test */
    public function when_user_has_tickets_it_returns_proper_item()
    {
        $company = Company::factory()->create();

        $response = $this->get("api/c/{$company->id}/tickets", []);
        $this->assertSame(403, $response->status());

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'company_id' => $company->id]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->get("api/c/{$company->id}/tickets", []);
        $this->assertSame(200, $response->status());

        $j = $response->json();
        $this->assertArrayHasKey('data', $j);
        $this->assertIsArray($j['data']);
        $data = $j['data'];
        $this->assertEquals(1, count($data));

        $this->assertIsArray($data[0]);
        $out = $data[0];
        foreach ([
            'user_id',
            'company_id',
            'trash_type_id',
            'note',
            'phone',
            'image'
        ] as $field) {
            $this->assertEquals($ticket->$field, $out[$field]);
        }

        // TODO: GEOMETRY (da fare con location)
        // $ticket_geojson = DB::select("SELECT st_asgeojson('{$ticket->geometry}') as g")[0]->g;
        // $out_geojson = DB::select("SELECT st_asgeojson('{$out['geometry']}') as g")[0]->g;
        // $this->assertEquals($ticket_geojson,$out_geojson);
    }
}
