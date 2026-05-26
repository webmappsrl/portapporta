<?php

namespace Tests\Feature\V2;

use App\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Feature\V2\FeatureTestV2UtilsFunctions;

class TicketFormsConfigControllerTest extends TestCase
{
    use DatabaseTransactions, FeatureTestV2UtilsFunctions;

    protected Company $company;

    const TICKET_TYPES = ['report', 'abandonment', 'reservation', 'info'];

    const DEFAULT_FINAL_MESSAGE = 'Puoi visualizzarla nella sezione "i miei ticket".';

    public function setUp(): void
    {
        parent::setUp();
        $this->company = $this->createCompany();
    }

    private function url(string $prefix = 'v2'): string
    {
        $base = $prefix === '' ? '/api/c/' : "/api/{$prefix}/c/";
        return $base . "{$this->company->id}/ticket-forms-config";
    }

    // ── Accessibilità ─────────────────────────────────────────────────────────

    /** @test */
    public function endpoint_is_publicly_accessible_without_authentication(): void
    {
        $this->get($this->url())->assertStatus(200);
    }

    /** @test */
    public function endpoint_is_accessible_on_all_api_versions(): void
    {
        $this->get($this->url(''))->assertStatus(200);
        $this->get($this->url('v1'))->assertStatus(200);
        $this->get($this->url('v2'))->assertStatus(200);
    }

    /** @test */
    public function endpoint_returns_404_for_nonexistent_company(): void
    {
        $this->get('/api/v2/c/999999/ticket-forms-config')->assertStatus(404);
    }

    // ── Struttura risposta ────────────────────────────────────────────────────

    /** @test */
    public function response_contains_all_four_ticket_types(): void
    {
        $data = $this->get($this->url())->assertStatus(200)->json('data');

        foreach (self::TICKET_TYPES as $type) {
            $this->assertArrayHasKey($type, $data, "Ticket type '$type' mancante nella risposta");
        }
    }

    /** @test */
    public function each_ticket_type_has_required_top_level_fields(): void
    {
        $data = $this->get($this->url())->json('data');

        foreach (self::TICKET_TYPES as $type) {
            $config = $data[$type];
            $this->assertArrayHasKey('ticketType', $config, "$type: campo 'ticketType' mancante");
            $this->assertArrayHasKey('label', $config, "$type: campo 'label' mancante");
            $this->assertArrayHasKey('cancel', $config, "$type: campo 'cancel' mancante");
            $this->assertArrayHasKey('finalMessage', $config, "$type: campo 'finalMessage' mancante");
            $this->assertArrayHasKey('pages', $config, "$type: campo 'pages' mancante");
            $this->assertArrayHasKey('step', $config, "$type: campo 'step' mancante");
        }
    }

    /** @test */
    public function ticket_type_field_matches_the_key(): void
    {
        $data = $this->get($this->url())->json('data');

        foreach (self::TICKET_TYPES as $type) {
            $this->assertEquals($type, $data[$type]['ticketType']);
        }
    }

    /** @test */
    public function each_step_has_required_fields(): void
    {
        $data = $this->get($this->url())->json('data');

        foreach (self::TICKET_TYPES as $type) {
            foreach ($data[$type]['step'] as $i => $step) {
                $this->assertArrayHasKey('label', $step, "$type step[$i]: 'label' mancante");
                $this->assertArrayHasKey('type', $step, "$type step[$i]: 'type' mancante");
                $this->assertArrayHasKey('required', $step, "$type step[$i]: 'required' mancante");
            }
        }
    }

    // ── Messaggi finali ───────────────────────────────────────────────────────

    /** @test */
    public function all_types_have_the_default_final_message(): void
    {
        $data = $this->get($this->url())->json('data');

        foreach (self::TICKET_TYPES as $type) {
            $this->assertEquals(
                self::DEFAULT_FINAL_MESSAGE,
                $data[$type]['finalMessage'],
                "finalMessage di default errato per '$type'"
            );
        }
    }

    // ── Pagine ────────────────────────────────────────────────────────────────

    /** @test */
    public function info_has_4_pages(): void
    {
        $data = $this->get($this->url())->json('data');
        $this->assertEquals(4, $data['info']['pages']);
    }

    /** @test */
    public function non_info_types_have_6_pages(): void
    {
        $data = $this->get($this->url())->json('data');

        foreach (['report', 'abandonment', 'reservation'] as $type) {
            $this->assertEquals(6, $data[$type]['pages'], "'$type' dovrebbe avere 6 pages");
        }
    }

    // ── Step specifici per tipo ───────────────────────────────────────────────

    /** @test */
    public function report_contains_calendar_trash_type_id_step(): void
    {
        $data = $this->get($this->url())->json('data');
        $types = array_column($data['report']['step'], 'type');
        $this->assertContains('calendar_trash_type_id', $types);
    }

    /** @test */
    public function abandonment_contains_trash_type_id_and_location_steps(): void
    {
        $data = $this->get($this->url())->json('data');
        $types = array_column($data['abandonment']['step'], 'type');
        $this->assertContains('trash_type_id', $types);
        $this->assertContains('location', $types);
    }

    /** @test */
    public function reservation_contains_trash_type_id_and_location_steps(): void
    {
        $data = $this->get($this->url())->json('data');
        $types = array_column($data['reservation']['step'], 'type');
        $this->assertContains('trash_type_id', $types);
        $this->assertContains('location', $types);
    }

    /** @test */
    public function each_form_ends_with_recap_step(): void
    {
        $data = $this->get($this->url())->json('data');

        foreach (self::TICKET_TYPES as $type) {
            $steps = $data[$type]['step'];
            $lastStep = end($steps);
            $this->assertEquals('recap', $lastStep['type'], "'$type': l'ultimo step dovrebbe essere 'recap'");
        }
    }

    /** @test */
    public function each_form_starts_with_label_step(): void
    {
        $data = $this->get($this->url())->json('data');

        foreach (self::TICKET_TYPES as $type) {
            $this->assertEquals('label', $data[$type]['step'][0]['type'], "'$type': il primo step dovrebbe essere 'label'");
        }
    }

    // ── Interpolazione nome azienda ───────────────────────────────────────────

    /** @test */
    public function company_name_is_interpolated_in_first_step_label(): void
    {
        $data = $this->get($this->url())->json('data');

        foreach (self::TICKET_TYPES as $type) {
            $firstLabel = $data[$type]['step'][0]['label'];
            $this->assertStringContainsString(
                $this->company->name,
                $firstLabel,
                "'$type': il nome azienda non è presente nel primo step"
            );
        }
    }

    /** @test */
    public function different_companies_return_their_own_name(): void
    {
        $otherCompany = Company::factory()->create(['name' => 'AltroComune SRL']);

        $data = $this->get("/api/v2/c/{$otherCompany->id}/ticket-forms-config")->json('data');

        $this->assertStringContainsString('AltroComune SRL', $data['report']['step'][0]['label']);
    }
}
