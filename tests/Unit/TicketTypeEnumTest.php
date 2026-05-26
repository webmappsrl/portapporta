<?php

namespace Tests\Unit;

use App\Enums\TicketType;
use Tests\TestCase;

class TicketTypeEnumTest extends TestCase
{
    // ── values() ─────────────────────────────────────────────────────────────

    /** @test */
    public function values_returns_all_four_types(): void
    {
        $values = TicketType::values();

        $this->assertCount(4, $values);
        $this->assertContains('report', $values);
        $this->assertContains('abandonment', $values);
        $this->assertContains('reservation', $values);
        $this->assertContains('info', $values);
    }

    // ── label() ───────────────────────────────────────────────────────────────

    /** @test */
    public function each_case_returns_a_non_empty_label(): void
    {
        foreach (TicketType::cases() as $type) {
            $this->assertNotEmpty($type->label(), "{$type->value}: label non dovrebbe essere vuota");
        }
    }

    // ── finalMessage() ────────────────────────────────────────────────────────

    /** @test */
    public function all_types_have_the_default_final_message(): void
    {
        foreach (TicketType::cases() as $type) {
            $this->assertStringContainsString(
                'i miei ticket',
                $type->finalMessage(),
                "{$type->value}: finalMessage dovrebbe contenere il testo di default"
            );
        }
    }

    // ── config() ──────────────────────────────────────────────────────────────

    /** @test */
    public function config_contains_all_required_top_level_keys(): void
    {
        foreach (TicketType::cases() as $type) {
            $config = $type->config('TestAzienda');
            $this->assertArrayHasKey('ticketType', $config);
            $this->assertArrayHasKey('label', $config);
            $this->assertArrayHasKey('cancel', $config);
            $this->assertArrayHasKey('finalMessage', $config);
            $this->assertArrayHasKey('pages', $config);
            $this->assertArrayHasKey('step', $config);
        }
    }

    /** @test */
    public function config_ticketType_matches_case_value(): void
    {
        foreach (TicketType::cases() as $type) {
            $this->assertEquals($type->value, $type->config('X')['ticketType']);
        }
    }

    /** @test */
    public function config_interpolates_company_name_in_first_step(): void
    {
        foreach (TicketType::cases() as $type) {
            $firstLabel = $type->config('ERSU SRL')['step'][0]['label'];
            $this->assertStringContainsString('ERSU SRL', $firstLabel, "{$type->value}: company name non interpolato");
        }
    }

    /** @test */
    public function config_info_has_4_pages(): void
    {
        $this->assertEquals(4, TicketType::Info->config('X')['pages']);
    }

    /** @test */
    public function config_non_info_types_have_6_pages(): void
    {
        foreach ([TicketType::Report, TicketType::Abandonment, TicketType::Reservation] as $type) {
            $this->assertEquals(6, $type->config('X')['pages'], "{$type->value} dovrebbe avere 6 pages");
        }
    }

    /** @test */
    public function config_steps_are_non_empty_arrays(): void
    {
        foreach (TicketType::cases() as $type) {
            $steps = $type->config('X')['step'];
            $this->assertIsArray($steps);
            $this->assertNotEmpty($steps, "{$type->value}: steps non dovrebbe essere vuoto");
        }
    }

    /** @test */
    public function config_each_step_has_label_type_required(): void
    {
        foreach (TicketType::cases() as $type) {
            foreach ($type->config('X')['step'] as $i => $step) {
                $this->assertArrayHasKey('label', $step, "{$type->value} step[$i]: 'label' mancante");
                $this->assertArrayHasKey('type', $step, "{$type->value} step[$i]: 'type' mancante");
                $this->assertArrayHasKey('required', $step, "{$type->value} step[$i]: 'required' mancante");
            }
        }
    }

    /** @test */
    public function config_report_includes_calendar_trash_type_id_step(): void
    {
        $types = array_column(TicketType::Report->config('X')['step'], 'type');
        $this->assertContains('calendar_trash_type_id', $types);
    }

    /** @test */
    public function config_abandonment_includes_trash_type_id_and_location_steps(): void
    {
        $types = array_column(TicketType::Abandonment->config('X')['step'], 'type');
        $this->assertContains('trash_type_id', $types);
        $this->assertContains('location', $types);
    }

    /** @test */
    public function config_reservation_includes_trash_type_id_and_location_steps(): void
    {
        $types = array_column(TicketType::Reservation->config('X')['step'], 'type');
        $this->assertContains('trash_type_id', $types);
        $this->assertContains('location', $types);
    }
}
