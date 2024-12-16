<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Company;
use App\Models\Ticket;
use App\Enums\TicketStatus;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketCreated;
use App\Mail\TicketDeleted;
use Spatie\Permission\Models\Role;
class TicketControllerTest extends TestCase
{
    use DatabaseTransactions;
    protected $user;
    protected $vipUser;
    protected $dustyMan;
    protected $company;


    public function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'vip']);
        Role::create(['name' => 'dusty_man']);
        $this->user = User::factory()->create();
        $this->vipUser = User::factory()->create()->assignRole('vip');
        $this->dustyMan = User::factory()->create()->assignRole('dusty_man');
        $this->company = Company::factory()->create([
            'ticket_email' => 'test@example.com'
        ]);
    }

    /** @test */
    public function testIndexAsDustyMan()
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->vipUser->id,
            'company_id' => $this->company->id,
            'status' => TicketStatus::New->value,
        ]);
        
        Sanctum::actingAs(
            $this->dustyMan,
        );

        $this->get("/api/v2/c/{$this->company->id}/tickets")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $ticket->id]);
    }

    /** @test */
    public function testIndexAsDustyManExcludesInvalidTickets()
    {
        $otherCompany = Company::factory()->create();

        $nonNewTicket = Ticket::factory()->create([
            'user_id' => $this->vipUser->id,
            'company_id' => $this->company->id,
            'status' => TicketStatus::Execute->value
        ]);

        $otherCompanyTicket = Ticket::factory()->create([
            'user_id' => $this->vipUser->id,
            'company_id' => $otherCompany->id,
            'status' => TicketStatus::New->value
        ]);

        Sanctum::actingAs(
            $this->dustyMan,
        );

        $this->get("/api/v2/c/{$this->company->id}/tickets")
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $nonNewTicket->id])
            ->assertJsonMissing(['id' => $otherCompanyTicket->id]);
    }

    /** @test */
    public function testIndexAsRegularUser()
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => TicketStatus::Execute->value,
        ]);
        
        Sanctum::actingAs(
            $this->user,
        );

        $this->get("/api/v2/c/{$this->company->id}/tickets")
            ->assertStatus(200)
            ->assertJsonMissing(["id" => (string)$ticket->id]);
    }

    /** @test */
    public function testIndexAsRegularUserExcludesDoneAndDeletedTickets()
    {

        $doneTicket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => TicketStatus::Done->value,
        ]);
        $deletedTicket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => TicketStatus::Deleted->value,
        ]);

        Sanctum::actingAs(
            $this->user,
        );
        
        $this->get("/api/v2/c/{$this->company->id}/tickets")
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $doneTicket->id])
            ->assertJsonMissing(['id' => $deletedTicket->id]);
    }

    /** @test */
    public function testTicketsAsRegularUserAreOrdered()
    {
        // Create two tickets with different creation dates
        $olderTicket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => TicketStatus::Execute->value,
            'created_at' => now()->subDays(2)
        ]);

        $newerTicket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'status' => TicketStatus::Execute->value,
            'created_at' => now()->subDay()
        ]);

        Sanctum::actingAs(
            $this->user,
        );

        $response = $this->get("/api/v2/c/{$this->company->id}/tickets")
            ->assertStatus(200);
            
        $responseData = json_decode($response->getContent(), true);
        $tickets = $responseData['data'];

        $this->assertEquals($newerTicket->id, $tickets[0]['id']);
        $this->assertEquals($olderTicket->id, $tickets[1]['id']);
    }

    /** @test */
    public function testV1StoreSuccessfulTicketCreation()
    {
        Sanctum::actingAs($this->user);

        Mail::fake();

        $ticket = [
            'ticket_type' => 'reservation',
            'note' => 'Test note',
            'phone' => '1234567890',
            'city' => 'Test City',
            'address' => 'Test Street',
            'house_number' => '123'
        ];

        $this->post("/api/v2/c/{$this->company->id}/ticket", $ticket)
        ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('success')
                    ->has('data', fn (AssertableJson $json) =>
                        $json->has('id')
                        ->where('ticket_type', 'reservation')
                        ->where('company_id', (string)$this->company->id)
                        ->where('user_id', $this->user->id)
                        ->where('note', 'Test note')
                        ->where('phone', '1234567890')
                        ->where('location_address', 'Test City, Test Street, 123')
                        ->etc()
                    )
                    ->where('message', 'Ticket created.')
            );

        Mail::assertSent(TicketCreated::class);

        $this->assertDatabaseHas('tickets', [
            'ticket_type' => 'reservation',
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'note' => 'Test note',
            'phone' => '1234567890',
            'location_address' => 'Test City, Test Street, 123'
        ]);

    }

    /** @test */
    public function testV1StoreValidatesTicketType()
    {
        $ticket = [
            'ticket_type' => 'invalid_type'
        ];

        Sanctum::actingAs($this->user);

        $this->post("/api/v2/c/{$this->company->id}/ticket", $ticket)        
            ->assertStatus(400)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('success', false)
                 ->where('message', 'The selected ticket type is invalid.')
        );

    }

    /** @test */
    public function testV1Update()
    {
        $ticket = Ticket::factory()->create();

        $changes = [
            'note' => 'updated note',
        ];

        $this->patch("/api/v2/ticket/{$ticket->id}", $changes)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('message', 'Ticket updated.')
                     ->etc()
        );

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'note' => 'updated note',
        ]);
    }

    /** @test */
    public function testV1UpdateSendsEmailWhenTicketIsDeleted()
    {
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Mail::fake();

        $changes = [
            'status' => TicketStatus::Deleted->value,
        ];

        $this->patch("/api/v2/ticket/{$ticket->id}", $changes)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('message', 'Ticket updated.')
                     ->etc()
        );

        Mail::assertSent(TicketDeleted::class);


        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => TicketStatus::Deleted->value,
        ]);
    }

    /** @test */
    public function testV1UpdateNonexistantTicket()
    {
        $response = $this->patch("/api/v2/ticket/0", []);

        $response->assertStatus(404);
    }
}
