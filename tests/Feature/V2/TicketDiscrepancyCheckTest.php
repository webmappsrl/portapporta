<?php

namespace Tests\Feature\V2;

use App\Models\Company;
use App\Models\Address;
use App\Models\Ticket;
use App\Models\Zone;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;

class TicketDiscrepancyCheckTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;

    private Company $company;
    private Zone $zoneA;
    private Zone $zoneB;

    const API_PREFIX_COMPANY = '/api/v2/c/';
    const API_PREFIX_TICKET  = '/api/v2/ticket/';

    // Zone A: lon 10.0-10.5, lat 43.5-44.0 (simula area Pietrasanta)
    const ZONE_A_POLYGON = [[10.0, 43.5], [10.5, 43.5], [10.5, 44.0], [10.0, 44.0], [10.0, 43.5]];
    const POINT_IN_A = ['lon' => 10.24, 'lat' => 43.96];

    // Zone B: lon 9.6-10.0, lat 44.0-44.5 (simula area Fosdinovo)
    const ZONE_B_POLYGON = [[9.6, 44.0], [10.0, 44.0], [10.0, 44.5], [9.6, 44.5], [9.6, 44.0]];
    const POINT_IN_B = ['lon' => 9.89, 'lat' => 44.13];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->company = $this->createCompany();
        $this->zoneA   = $this->makeZoneWithPolygon(self::ZONE_A_POLYGON, $this->company);
        $this->zoneB   = $this->makeZoneWithPolygon(self::ZONE_B_POLYGON, $this->company);
    }

    private function makeZoneWithPolygon(array $coords, Company $company): Zone
    {
        $wkt      = 'MULTIPOLYGON(((' . implode(', ', array_map(fn($p) => "{$p[0]} {$p[1]}", $coords)) . ')))';
        $geometry = DB::selectOne('SELECT ST_GeomFromText(?, 4326) AS g', [$wkt])->g;

        return Zone::create([
            'company_id' => $company->id,
            'comune'     => 'Test',
            'label'      => 'Test Zone',
            'url'        => 'http://test.example',
            'geometry'   => $geometry,
        ]);
    }

    private function mockNominatimWithPoint(float $lat, float $lon): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response(
                [['lat' => (string) $lat, 'lon' => (string) $lon]],
                200
            ),
        ]);
    }

    private function mockNominatimEmpty(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);
    }

    /** @test */
    public function testDiscrepancyDetectedOverridesZoneAndGeometry(): void
    {
        // Nominatim geocodifica il testo → punto in zona B
        $this->mockNominatimWithPoint(self::POINT_IN_B['lat'], self::POINT_IN_B['lon']);

        $user = $this->createUser();
        Sanctum::actingAs($user);

        // location → zona A, testo → zona B
        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type' => 'reservation',
            'address'     => 'Via Giovanni XXIII',
            'house_number' => '9',
            'city'        => 'Fosdinovo',
            'location'    => [self::POINT_IN_A['lon'], self::POINT_IN_A['lat']],
        ]);

        $response->assertStatus(200);
        $ticketId = $response->json('data.id');

        $this->assertDatabaseHas('tickets', [
            'id'      => $ticketId,
            'zone_id' => $this->zoneB->id,
        ]);

        $this->assertDatabaseMissing('tickets', [
            'id'      => $ticketId,
            'zone_id' => $this->zoneA->id,
        ]);
    }

    /** @test */
    public function testNoDiscrepancyKeepsOriginalZone(): void
    {
        // Nominatim geocodifica il testo → punto ancora in zona A
        $this->mockNominatimWithPoint(self::POINT_IN_A['lat'], self::POINT_IN_A['lon']);

        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type' => 'reservation',
            'address'     => 'Via Roma',
            'house_number' => '1',
            'city'        => 'Pietrasanta',
            'location'    => [self::POINT_IN_A['lon'], self::POINT_IN_A['lat']],
        ]);

        $response->assertStatus(200);
        $ticketId = $response->json('data.id');

        $this->assertDatabaseHas('tickets', [
            'id'      => $ticketId,
            'zone_id' => $this->zoneA->id,
        ]);
    }

    /** @test */
    public function testNominatimNoResultsIsFailOpen(): void
    {
        $this->mockNominatimEmpty();

        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type' => 'reservation',
            'address'     => 'Via Inesistente',
            'city'        => 'ComuneNonEsiste',
            'location'    => [self::POINT_IN_A['lon'], self::POINT_IN_A['lat']],
        ]);

        $response->assertStatus(200);
        $ticketId = $response->json('data.id');

        // fail-open: zona derivata dalle coordinate originali (zona A)
        $this->assertDatabaseHas('tickets', [
            'id'      => $ticketId,
            'zone_id' => $this->zoneA->id,
        ]);
    }

    /** @test */
    public function testNominatimUnreachableIsFailOpen(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Nominatim unreachable');
            },
        ]);

        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type' => 'reservation',
            'address'     => 'Via Giovanni XXIII',
            'city'        => 'Fosdinovo',
            'location'    => [self::POINT_IN_A['lon'], self::POINT_IN_A['lat']],
        ]);

        $response->assertStatus(200);
        $ticketId = $response->json('data.id');
        // fail-open: ticket creato con le coordinate originali (zona A)
        $this->assertDatabaseHas('tickets', [
            'id'      => $ticketId,
            'zone_id' => $this->zoneA->id,
        ]);
    }

    /** @test */
    public function testAddressIdPresentSkipsCheck(): void
    {
        // Nominatim non deve essere chiamato
        Http::fake();

        $user    = $this->createUser();
        $address = $this->createAddress($user, $this->zoneB);
        Sanctum::actingAs($user);

        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type' => 'reservation',
            'address_id'  => $address->id,
            'address'     => 'Via Test',
            'city'        => 'Fosdinovo',
            'location'    => [self::POINT_IN_A['lon'], self::POINT_IN_A['lat']],
        ]);

        $response->assertStatus(200);
        Http::assertNothingSent();
    }

    /** @test */
    public function testMissingCitySkipsCheck(): void
    {
        // Senza city la query Nominatim sarebbe troppo vaga: il check non parte
        Http::fake();

        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type' => 'reservation',
            'address'     => 'Via Roma',
            'location'    => [self::POINT_IN_A['lon'], self::POINT_IN_A['lat']],
        ]);

        $response->assertStatus(200);
        Http::assertNothingSent();
    }

    /** @test */
    public function testKillSwitchDisabledSkipsCheck(): void
    {
        config(['app.address_discrepancy_check_enabled' => false]);

        Http::fake();

        $user = $this->createUser();
        Sanctum::actingAs($user);

        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type' => 'reservation',
            'address'     => 'Via Giovanni XXIII',
            'city'        => 'Fosdinovo',
            'location'    => [self::POINT_IN_A['lon'], self::POINT_IN_A['lat']],
        ]);

        $response->assertStatus(200);
        Http::assertNothingSent();
        // zona non corretta: rimane quella delle coordinate originali (A)
        $ticketId = $response->json('data.id');
        $this->assertDatabaseHas('tickets', [
            'id'      => $ticketId,
            'zone_id' => $this->zoneA->id,
        ]);
    }

    /** @test */
    public function testV1UpdateDiscrepancyOverridesExistingZone(): void
    {
        // Ticket già salvato con zona A
        $user   = $this->createUser();
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'user_id'    => $user->id,
            'zone_id'    => $this->zoneA->id,
        ]);

        // Update porta location in A ma testo verso B
        $this->mockNominatimWithPoint(self::POINT_IN_B['lat'], self::POINT_IN_B['lon']);

        Sanctum::actingAs($user);

        $this->patch(self::API_PREFIX_TICKET . "{$ticket->id}", [
            'address'     => 'Via Giovanni XXIII',
            'house_number' => '9',
            'city'        => 'Fosdinovo',
            'location'    => [self::POINT_IN_A['lon'], self::POINT_IN_A['lat']],
        ]);

        $this->assertDatabaseHas('tickets', [
            'id'      => $ticket->id,
            'zone_id' => $this->zoneB->id,
        ]);
    }
}
