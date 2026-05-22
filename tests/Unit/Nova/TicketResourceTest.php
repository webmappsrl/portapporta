<?php

namespace Tests\Unit\Nova;

use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Zone;
use App\Nova\Ticket as TicketResource;
use Laravel\Nova\Http\Requests\NovaRequest;
use ReflectionMethod;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TicketResourceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function detailQueryEagerLoadsUserAndCompany(): void
    {
        $query = Ticket::query();
        $result = TicketResource::detailQuery(NovaRequest::create('/nova-api/tickets/1', 'GET'), $query);

        $this->assertArrayHasKey('user', $result->getEagerLoads());
        $this->assertArrayHasKey('company', $result->getEagerLoads());
    }

    /** @test */
    public function userFormDataFieldsAddsReadOnlyFieldsFromUserFormData(): void
    {
        $company = Company::factory()->create([
            'form_json' => json_encode([
                ['name' => 'fiscal_code', 'label' => 'Codice fiscale', 'type' => 'text'],
                ['name' => 'secret', 'label' => 'Secret', 'type' => 'password'],
                ['name' => 'address_group', 'label' => 'Indirizzo', 'type' => 'group'],
                ['name' => 'internal', 'label' => 'Solo FE', 'type' => 'text', 'only_fe' => true],
            ]),
        ]);
        $user = User::factory()->create([
            'app_company_id' => $company->id,
            'form_data' => ['Codice fiscale' => 'CF123456'],
        ]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
        ]);

        $resource = new TicketResource($ticket);
        $fields = [];

        $method = new ReflectionMethod(TicketResource::class, '_userFormDataFields');
        $method->setAccessible(true);
        $method->invokeArgs($resource, [&$fields]);

        $request = NovaRequest::create('/nova-api/tickets/1', 'GET');
        $this->assertCount(2, $fields);
        $this->assertTrue($fields[0]->isReadonly($request));
        $this->assertTrue($fields[1]->isReadonly($request));
    }

    /** @test */
    public function userFormDataFieldsDoesNothingWithoutUserOrFormJson(): void
    {
        $company = Company::factory()->create(['form_json' => null]);
        $ticket = Ticket::factory()->create(['company_id' => $company->id]);

        $resource = new TicketResource($ticket);
        $fields = [];

        $method = new ReflectionMethod(TicketResource::class, '_userFormDataFields');
        $method->setAccessible(true);
        $method->invokeArgs($resource, [&$fields]);

        $this->assertCount(0, $fields);
    }

    /** @test */
    public function detailQueryEagerLoadsZoneRelations(): void
    {
        $query = Ticket::query();
        $result = TicketResource::detailQuery(NovaRequest::create('/nova-api/tickets/1', 'GET'), $query);

        $eagerLoads = $result->getEagerLoads();
        $this->assertArrayHasKey('zone', $eagerLoads);
        $this->assertArrayHasKey('address.zone', $eagerLoads);
    }

    /** @test */
    public function ticketBelongsToZone(): void
    {
        $zone = Zone::factory()->create();
        $ticket = Ticket::factory()->create(['zone_id' => $zone->id]);

        $this->assertEquals($zone->id, $ticket->zone->id);
    }
}
