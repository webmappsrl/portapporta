<?php

namespace Tests\Feature\Emails;

use App\Mail\TicketAnswer;
use App\Mail\TicketCreated;
use App\Mail\TicketDeleted;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Zone;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TicketEmailViewsTest extends TestCase
{
    use DatabaseTransactions;

    private function sampleFormJson(): array
    {
        return [
            ['name' => 'fiscal_code', 'label' => 'Codice fiscale', 'type' => 'text'],
            ['name' => 'phone_number', 'label' => 'Telefono', 'type' => 'text'],
            ['name' => 'internal', 'label' => 'Solo FE', 'type' => 'text', 'only_fe' => true],
            ['name' => 'secret', 'label' => 'Secret', 'type' => 'password'],
            ['name' => 'address_group', 'label' => 'Indirizzo', 'type' => 'group'],
        ];
    }

    private function createTicketContext(array $userOverrides = [], array $ticketOverrides = []): array
    {
        $company = Company::factory()->create([
            'form_json' => json_encode($this->sampleFormJson()),
        ]);
        $user = User::factory()->create([
            'app_company_id' => $company->id,
            'phone_number' => '3331112222',
            'form_data' => [
                'Codice fiscale' => 'CF123456',
                'phone_number' => '3331112222',
            ],
            ...$userOverrides,
        ]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'phone' => '3331112222',
            'geometry' => null,
            ...$ticketOverrides,
        ]);

        return compact('company', 'user', 'ticket');
    }

    /** @test */
    public function partialRendersDynamicFormFieldsAndExcludesPasswordAndGroup(): void
    {
        ['company' => $company, 'user' => $user, 'ticket' => $ticket] = $this->createTicketContext();

        $html = view('emails.tickets.partials.user-form-fields', [
            'user' => $user,
            'company' => $company,
            'ticket' => $ticket,
            'format' => 'br',
        ])->render();

        $this->assertStringContainsString('<strong>Codice fiscale:</strong> CF123456', $html);
        $this->assertStringContainsString('<strong>Telefono:</strong> 3331112222', $html);
        $this->assertStringContainsString('<strong>Solo FE:</strong>', $html);
        $this->assertStringNotContainsString('Secret:', $html);
        $this->assertStringNotContainsString('Indirizzo:', $html);
    }

    /** @test */
    public function partialUsesTicketPhoneWhenDifferentFromUser(): void
    {
        ['company' => $company, 'user' => $user, 'ticket' => $ticket] = $this->createTicketContext(
            ['phone_number' => '3331112222'],
            ['phone' => '3999999999']
        );

        $html = view('emails.tickets.partials.user-form-fields', [
            'user' => $user,
            'company' => $company,
            'ticket' => $ticket,
            'format' => 'br',
        ])->render();

        $this->assertStringContainsString('<strong>Telefono:</strong> 3999999999', $html);
    }

    /** @test */
    public function partialAppendsPhoneWhenNotPresentInFormJson(): void
    {
        $company = Company::factory()->create([
            'form_json' => json_encode([
                ['name' => 'fiscal_code', 'label' => 'Codice fiscale', 'type' => 'text'],
            ]),
        ]);
        $user = User::factory()->create([
            'app_company_id' => $company->id,
            'form_data' => ['Codice fiscale' => 'CF123456'],
        ]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'phone' => '3334445555',
            'geometry' => null,
        ]);

        $html = view('emails.tickets.partials.user-form-fields', [
            'user' => $user,
            'company' => $company,
            'ticket' => $ticket,
            'format' => 'br',
        ])->render();

        $this->assertStringContainsString('<strong>Codice fiscale:</strong> CF123456', $html);
        $this->assertStringContainsString('<strong>Telefono:</strong> 3334445555', $html);
    }

    /** @test */
    public function createdEmailIncludesDynamicUserFields(): void
    {
        ['company' => $company, 'ticket' => $ticket] = $this->createTicketContext();

        $html = (new TicketCreated($ticket, $company))->render();

        $this->assertStringContainsString('Segnalazione #' . $ticket->id, $html);
        $this->assertStringContainsString('Codice fiscale', $html);
        $this->assertStringContainsString('CF123456', $html);
        $this->assertStringContainsString('3331112222', $html);
    }

    /** @test */
    public function deletedEmailIncludesDynamicUserFields(): void
    {
        ['company' => $company, 'ticket' => $ticket] = $this->createTicketContext();

        $html = (new TicketDeleted($ticket, $company))->render();

        $this->assertStringContainsString('Segnalazione Cancellata #' . $ticket->id, $html);
        $this->assertStringContainsString('Codice fiscale', $html);
        $this->assertStringContainsString('CF123456', $html);
    }

    /** @test */
    public function answerEmailRendersDynamicFieldsAsParagraphs(): void
    {
        ['company' => $company, 'ticket' => $ticket] = $this->createTicketContext();

        $html = (new TicketAnswer($ticket, 'Risposta di test'))->render();

        $this->assertStringContainsString('<p><strong>Codice fiscale:</strong> CF123456</p>', $html);
        $this->assertStringContainsString('<p><strong>Telefono:</strong> 3331112222</p>', $html);
        $this->assertStringContainsString('Risposta di test', $html);
    }

    /** @test */
    public function createdEmailShowsZoneDataWhenTicketHasDirectZone(): void
    {
        $company = Company::factory()->create([
            'form_json' => json_encode($this->sampleFormJson()),
        ]);
        $zone = Zone::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['app_company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'zone_id' => $zone->id,
            'geometry' => null,
        ]);

        $html = (new TicketCreated($ticket, $company))->render();

        $this->assertStringContainsString($zone->label, $html);
        $this->assertStringContainsString($zone->comune, $html);
    }

    /** @test */
    public function deletedEmailShowsZoneDataWhenTicketHasDirectZone(): void
    {
        $company = Company::factory()->create([
            'form_json' => json_encode($this->sampleFormJson()),
        ]);
        $zone = Zone::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['app_company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'zone_id' => $zone->id,
            'geometry' => null,
        ]);

        $html = (new TicketDeleted($ticket, $company))->render();

        $this->assertStringContainsString($zone->label, $html);
        $this->assertStringContainsString($zone->comune, $html);
    }

    /** @test */
    public function partialRendersAccountEmailAndNameBeforeTariData(): void
    {
        ['company' => $company, 'user' => $user, 'ticket' => $ticket] = $this->createTicketContext();

        $html = view('emails.tickets.partials.user-form-fields', [
            'user' => $user,
            'company' => $company,
            'ticket' => $ticket,
            'format' => 'table',
        ])->render();

        $this->assertStringContainsString($user->email, $html);
        $this->assertStringContainsString($user->name, $html);
        $emailPos = strpos($html, $user->email);
        $tariPos  = strpos($html, 'CF123456');
        $this->assertLessThan($tariPos, $emailPos, 'Email account deve precedere i dati TARI');
    }

    /** @test */
    public function partialSkipsAccountFieldsWhenUserIsNull(): void
    {
        $html = view('emails.tickets.partials.user-form-fields', [
            'user' => null,
            'company' => null,
            'ticket' => null,
            'format' => 'br',
        ])->render();

        $this->assertSame('', trim($html));
    }

    /** @test */
    public function createdEmailUsesCompanyPrimaryColorInHeader(): void
    {
        $company = Company::factory()->create([
            'form_json' => json_encode($this->sampleFormJson()),
            'primary_color' => '#1a2b3c',
        ]);
        $user = User::factory()->create(['app_company_id' => $company->id]);
        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'geometry' => null,
        ]);

        $html = (new TicketCreated($ticket, $company))->render();

        $this->assertStringContainsString('#1a2b3c', $html);
    }
}
