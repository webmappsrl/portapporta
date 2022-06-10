<?php

namespace Database\Factories;

use App\Models\TrashType;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        try {
            $trash_type = TrashType::all()->random();
        } catch (Exception $e) {
            $company = TrashType::factory()->create();
        }
        try {
            $user = User::all()->random();
        } catch (Exception $e) {
            $user = User::factory()->create();
        }

        return [
            'ticket_type' => $this->faker->randomElement(['reservation', 'info','abandonment','report']),
            'company_id' => $trash_type->company->id,
            'trash_type_id' => $trash_type->id,
            'user_id' => $user->id,
            'geometry' => DB::select("SELECT ST_GeomFromText('POINT(10 45)') as g")[0]->g,
            'note' => $this->faker->sentence(100),
            'phone' => $this->faker->phoneNumber(),
        ];
    }
}
