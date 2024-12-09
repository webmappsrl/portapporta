<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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

class TicketControllerTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testIndexAsDustyMan()
    {
        $user = User::factory()->create();
        $user->assignRole('dusty_man');

        Sanctum::actingAs(
            $user,
        );

        $company = Company::factory()->create();

        $vipUser = User::factory()->create();
        $vipUser->assignRole('vip');

        $ticket = Ticket::factory()->create([
            'user_id' => $vipUser->id,
            'company_id' => $company->id,
            'status' => TicketStatus::New->value,
        ]);

        $response = $this->get("/api/v2/c/{$company->id}/tickets");
        $response->assertStatus(200);

        $response->assertJsonFragment(['id' => $ticket->id]);
    }

    public function testIndexAsDustyManExcludesInvalidTickets()
    {
        $user = User::factory()->create();
        $user->assignRole('dusty_man');

        Sanctum::actingAs(
            $user,
        );

        $company = Company::factory()->create();
        $vipUser = User::factory()->create();
        $vipUser->assignRole('vip');

        $nonNewTicket = Ticket::factory()->create([
            'user_id' => $vipUser->id,
            'company_id' => $company->id,
            'status' => TicketStatus::Execute->value
        ]);

        $otherCompany = Company::factory()->create();
        $otherCompanyTicket = Ticket::factory()->create([
            'user_id' => $vipUser->id,
            'company_id' => $otherCompany->id,
            'status' => TicketStatus::New->value
        ]);

        $response = $this->get("/api/v2/c/{$company->id}/tickets");
        $response->assertStatus(200);

        $response->assertJsonMissing(['id' => $nonNewTicket->id]);
        $response->assertJsonMissing(['id' => $otherCompanyTicket->id]);
    }

    public function testIndexAsRegularUser()
    {
        // Create a regular user
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
        );

        // Create a company and a ticket for the user
        $company = Company::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => TicketStatus::Execute->value,
        ]);

        // Act as the regular user
        $response = $this->get("/api/v2/c/{$company->id}/tickets");

        // Assert the response contains the ticket
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $ticket->id]);
    }

    public function testIndexAsRegularUserExcludesDoneAndDeletedTickets()
    {
        // Create a regular user
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
        );

        // Create a company and tickets for the user
        $company = Company::factory()->create();
        $doneTicket = Ticket::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => TicketStatus::Done->value,
        ]);
        $deletedTicket = Ticket::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => TicketStatus::Deleted->value,
        ]);

        // Act as the regular user
        $response = $this->get("/api/v2/c/{$company->id}/tickets");

        // Assert the response does not contain the done or deleted tickets
        $response->assertStatus(200);
        $response->assertJsonMissing(['id' => $doneTicket->id]);
        $response->assertJsonMissing(['id' => $deletedTicket->id]);
    }

    public function testTicketsAsRegularUserAreOrdered()
    {
        // Create a regular user
        $user = User::factory()->create();

        Sanctum::actingAs(
            $user,
        );

        // Create a company
        $company = Company::factory()->create();

        // Create two tickets with different creation dates
        $olderTicket = Ticket::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => TicketStatus::Execute->value,
            'created_at' => now()->subDays(2)
        ]);

        $newerTicket = Ticket::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'status' => TicketStatus::Execute->value,
            'created_at' => now()->subDay()
        ]);

        // Act as the regular user
        $response = $this->get("/api/v2/c/{$company->id}/tickets");

        // Assert response status
        $response->assertStatus(200);

        // Get the tickets from the response
        $responseData = json_decode($response->getContent(), true);
        $tickets = $responseData['data'];

        // Assert the newer ticket appears before the older ticket
        $this->assertEquals($newerTicket->id, $tickets[0]['id']);
        $this->assertEquals($olderTicket->id, $tickets[1]['id']);
    }

    public function testV1StoreSuccessfulTicketCreation()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $company = Company::factory()->create([
            'ticket_email' => 'test@example.com'
        ]);

        Mail::fake();

        $ticket = [
            'ticket_type' => 'reservation',
            'note' => 'Test note',
            'phone' => '1234567890',
            'city' => 'Test City',
            'address' => 'Test Street',
            'house_number' => '123'
        ];

        $response = $this->post("/api/v2/c/{$company->id}/ticket", $ticket);

        $response->assertStatus(200);

        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('success')
                 ->has('data', fn (AssertableJson $json) =>
                     $json->has('id')
                          ->where('ticket_type', 'reservation')
                          ->where('company_id', (string)$company->id)
                          ->where('user_id', $user->id)
                          ->etc()
                 )
                 ->where('message', 'Ticket created.')
        );

        Mail::assertSent(TicketCreated::class);

        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('message', 'Ticket created.')
                    ->etc()
        );

        $this->assertDatabaseHas('tickets', [
            'ticket_type' => 'reservation',
            'company_id' => $company->id,
            'user_id' => $user->id,
        ]);

    }

    public function testV1StoreValidatesTicketType()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $company = Company::factory()->create();


        $ticket = [
            'ticket_type' => 'invalid_type',
            'note' => 'Test note',
            'phone' => '1234567890',
            'city' => 'Test City',
            'address' => 'Test Street',
            'house_number' => '123'
        ];

        $response = $this->post("/api/v2/c/{$company->id}/ticket", $ticket);
        
        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('success', false)
                 ->where('message', 'The selected ticket type is invalid.')
        );

        $response->assertStatus(400);

    }

    public function testV1Update()
    {
        $ticket = Ticket::factory()->create();

        $changes = [
            'note' => 'updated note',
        ];

        $response = $this->patch("/api/v2/ticket/{$ticket->id}", $changes);

        $response->assertStatus(200);

        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('message', 'Ticket updated.')
                 ->etc()
        );

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'note' => 'updated note',
        ]);
    }

    public function testV1UpdateSendsEmailWhenTicketIsDeleted()
    {
        $company = Company::factory()->create([
            'ticket_email' => 'test@example.com'
        ]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
        ]);

        Mail::fake();

        $changes = [
            'status' => TicketStatus::Deleted->value,
        ];

        $response = $this->patch("/api/v2/ticket/{$ticket->id}", $changes);

        $response->assertStatus(200);

        $response->assertJson(fn (AssertableJson $json) =>
            $json->where('message', 'Ticket updated.')
                 ->etc()
        );

        Mail::assertSent(TicketDeleted::class);


        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => TicketStatus::Deleted->value,
        ]);
    }

    public function testV1UpdateNonexistantTicket()
    {
        $response = $this->patch("/api/v2/ticket/1234567890", []);

        $response->assertStatus(404);
    }
}
