<?php

namespace Tests\Feature\V2;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Company;
use App\Models\Ticket;
use App\Enums\TicketStatus;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Facades\Mail;
use App\Mail\TicketCreated;
use App\Mail\TicketDeleted;
use Spatie\Permission\Models\Role;
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;
class TicketControllerTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;
    protected $user;
    protected $vipUser;
    protected $dustyMan;
    protected $company;
    const API_PREFIX_COMPANY = '/api/v2/c/';
    const API_PREFIX_TICKET = '/api/v2/ticket/';
    const responseMessages = [
        'ticketsFetched'       => 'User tickets',
        'ticketCreated'        => 'Ticket created.',
        'invalidTicketType'    => 'The selected ticket type is invalid.',
        'ticketUpdated'        => 'Ticket updated.',
        'ticketNotFound'       => 'Ticket not found.',
        'collectionInProgress' => 'Il giro di raccolta è ancora in corso, riprova più tardi.',
    ];

    public function setUp(): void
    {
        parent::setUp();
        if (!Role::where('name', 'vip')->exists()) {
            Role::create(['name' => 'vip']);
        }
        if (!Role::where('name', 'dusty_man')->exists()) {
            Role::create(['name' => 'dusty_man']);
        }
        $this->user = $this->createUser();
        $this->vipUser = $this->createUser()->assignRole('vip');
        $this->dustyMan = $this->createUser()->assignRole('dusty_man');
        $this->company = $this->createCompany();
    }

    /** @test */
    public function testIndexAsDustyMan()
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->vipUser->id,
            'company_id' => $this->company->id,
            'status' => TicketStatus::New->value,
        ]);
        
        Sanctum::actingAs($this->dustyMan);

        $response = $this->get(self::API_PREFIX_COMPANY . "{$this->company->id}/tickets");
        $this->assertSuccessResponse($response, self::responseMessages['ticketsFetched']);
        $response->assertJsonFragment(['id' => $ticket->id]);
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

        $response = $this->get(self::API_PREFIX_COMPANY . "{$this->company->id}/tickets");
        $this->assertSuccessResponse($response, self::responseMessages['ticketsFetched']);
        $response->assertJsonMissing(['id' => $nonNewTicket->id])
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

        $response = $this->get(self::API_PREFIX_COMPANY . "{$this->company->id}/tickets");
        $this->assertSuccessResponse($response, self::responseMessages['ticketsFetched']);
        $response->assertJsonMissing(["id" => (string)$ticket->id]);
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

        Sanctum::actingAs($this->user);

        $response = $this->get(self::API_PREFIX_COMPANY . "{$this->company->id}/tickets");
        $this->assertSuccessResponse($response, self::responseMessages['ticketsFetched']);
        $response->assertJsonMissing(['id' => $doneTicket->id])
                ->assertJsonMissing(['id' => $deletedTicket->id]);
    }

    /** @test */
    public function testTicketsAsRegularUserAreOrdered()
    {
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

        Sanctum::actingAs($this->user);

        $response = $this->get(self::API_PREFIX_COMPANY . "{$this->company->id}/tickets");
        $this->assertSuccessResponse($response, self::responseMessages['ticketsFetched']);
            
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

        $ticketFieldsToSend= [
            'ticket_type' => 'reservation',
            'note' => 'Test note',
            'phone' => '1234567890',
            'city' => 'Test City',
            'address' => 'Test Street',
            'house_number' => '123'
        ];

        $ticketFieldsToCheck = [
            'ticket_type' => $ticketFieldsToSend['ticket_type'],
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'note' => $ticketFieldsToSend['note'],
            'phone' => $ticketFieldsToSend['phone'],
            'location_address' => $ticketFieldsToSend['address'] . ', ' . $ticketFieldsToSend['house_number'] . ' — ' . $ticketFieldsToSend['city']
        ];


        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", $ticketFieldsToSend);
        $this->assertSuccessResponse($response, self::responseMessages['ticketCreated']);
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('data', fn (AssertableJson $json) =>
                $json->has('id')
                    ->where('ticket_type', $ticketFieldsToCheck['ticket_type'])
                    ->where('company_id', (string)$ticketFieldsToCheck['company_id'])
                    ->where('user_id', $ticketFieldsToCheck['user_id'])
                    ->where('note', $ticketFieldsToCheck['note'])
                    ->where('phone', $ticketFieldsToCheck['phone'])
                    ->where('location_address', $ticketFieldsToCheck['location_address'])
                    ->etc()
                    )
                    ->etc()
            );

        Mail::assertSent(TicketCreated::class);

        $this->assertDatabaseHas('tickets', $ticketFieldsToCheck);

    }

    /** @test */
    public function testV1StoreValidatesTicketType()
    {
        $ticket = [
            'ticket_type' => 'invalid_type'
        ];

        Sanctum::actingAs($this->user);

        $this->assertErrorResponse(
            $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", $ticket),
            self::responseMessages['invalidTicketType'],
            400
        );


    }

    /** @test */
    public function testV1Update()
    {
        $ticket = Ticket::factory()->create();

        $changes = [
            'note' => 'updated note',
        ];

        $this->assertSuccessResponse(
            $this->patch(self::API_PREFIX_TICKET . "{$ticket->id}", $changes),
            self::responseMessages['ticketUpdated']
        );

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'note' => $changes['note'],
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

        $this->assertSuccessResponse(
            $this->patch(self::API_PREFIX_TICKET . "{$ticket->id}", $changes),
            self::responseMessages['ticketUpdated']
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
        $this->patch(SELF::API_PREFIX_TICKET."0", [])
            ->assertStatus(404);
    }

    /** @test */
    public function testV1StoreWithZoneIdSavesZone(): void
    {
        $zone = $this->createZone($this->company);

        Sanctum::actingAs($this->user);
        Mail::fake();

        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type' => 'reservation',
            'zone_id' => $zone->id,
        ]);

        $this->assertSuccessResponse($response, self::responseMessages['ticketCreated']);

        $this->assertDatabaseHas('tickets', [
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'zone_id' => $zone->id,
        ]);
    }

    /** @test */
    public function testV1StoreLocationAddressIncludesCity(): void
    {
        Sanctum::actingAs($this->user);
        Mail::fake();

        $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type' => 'info',
            'city' => 'Test City',
            'address' => 'Via Roma',
            'house_number' => '10',
        ]);

        $this->assertDatabaseHas('tickets', [
            'location_address' => 'Via Roma, 10 — Test City',
        ]);
    }

    /** @test */
    public function testStoreWithZoneIdSavesZone(): void
    {
        $zone = $this->createZone($this->company);

        Sanctum::actingAs($this->user);
        Mail::fake();

        $response = $this->post('/api/c/' . "{$this->company->id}/ticket", [
            'ticket_type' => 'info',
            'zone_id' => $zone->id,
        ]);

        $this->assertSuccessResponse($response, self::responseMessages['ticketCreated']);

        $this->assertDatabaseHas('tickets', [
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'zone_id' => $zone->id,
        ]);
    }

    /** @test */
    public function testV1StoreRejectsMissedWithdrawWhileRoundInProgress(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 0));
        $zone = $this->createZone($this->company);
        $this->createCalendarWithStopTime($zone, '12:00');

        Sanctum::actingAs($this->user);
        Mail::fake();

        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type'          => 'report',
            'zone_id'              => $zone->id,
            'missed_withdraw_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $this->assertErrorResponse($response, self::responseMessages['collectionInProgress'], 400);

        Carbon::setTestNow();
    }

    /** @test */
    public function testV1StoreAllowsMissedWithdrawAfterRoundEnds(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(13, 0));
        $zone = $this->createZone($this->company);
        $this->createCalendarWithStopTime($zone, '12:00');

        Sanctum::actingAs($this->user);
        Mail::fake();

        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type'          => 'report',
            'zone_id'              => $zone->id,
            'missed_withdraw_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $this->assertSuccessResponse($response, self::responseMessages['ticketCreated']);

        Carbon::setTestNow();
    }

    /** @test */
    public function testV1StoreAllowsMissedWithdrawWithoutZoneId(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 0));

        Sanctum::actingAs($this->user);
        Mail::fake();

        $response = $this->post(self::API_PREFIX_COMPANY . "{$this->company->id}/ticket", [
            'ticket_type'          => 'report',
            'missed_withdraw_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $this->assertSuccessResponse($response, self::responseMessages['ticketCreated']);

        Carbon::setTestNow();
    }

    /** @test */
    public function testV1UpdateRejectsMissedWithdrawWhileRoundInProgress(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 0));
        $zone = $this->createZone($this->company);
        $this->createCalendarWithStopTime($zone, '12:00');

        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'zone_id'    => $zone->id,
        ]);

        $response = $this->patch(self::API_PREFIX_TICKET . "{$ticket->id}", [
            'missed_withdraw_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $this->assertErrorResponse($response, self::responseMessages['collectionInProgress'], 400);

        Carbon::setTestNow();
    }

    /** @test */
    public function testV1UpdateSkipsCheckWhenMissedWithdrawDateNotInPayload(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(10, 0));
        $zone = $this->createZone($this->company);
        $this->createCalendarWithStopTime($zone, '12:00');

        $ticket = Ticket::factory()->create([
            'company_id'           => $this->company->id,
            'zone_id'              => $zone->id,
            'missed_withdraw_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $response = $this->patch(self::API_PREFIX_TICKET . "{$ticket->id}", [
            'note' => 'solo aggiornamento note',
        ]);

        $this->assertSuccessResponse($response, self::responseMessages['ticketUpdated']);

        Carbon::setTestNow();
    }

    private function createCalendarWithStopTime(\App\Models\Zone $zone, string $stopTime): void
    {
        $userType = $this->createUserType();
        $calendar = \App\Models\Calendar::factory()->create([
            'zone_id'      => $zone->id,
            'company_id'   => $this->company->id,
            'user_type_id' => $userType->id,
            'start_date'   => Carbon::today()->subDays(5),
            'stop_date'    => Carbon::today()->addDays(30),
        ]);
        \App\Models\CalendarItem::factory()->create([
            'calendar_id' => $calendar->id,
            'day_of_week' => Carbon::today()->dayOfWeek,
            'start_time'  => '08:00',
            'stop_time'   => $stopTime,
            'frequency'   => 'weekly',
        ]);
    }
}
