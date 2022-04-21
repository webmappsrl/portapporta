<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\TrashType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Waste>
 */
class WasteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'company_id' => Company::factory()->create(),
            'trash_type_id' => TrashType::factory()->create(),
        ];
    }
}
