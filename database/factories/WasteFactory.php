<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\TrashType;
use Exception;
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
        try {
            $company = Company::all()->random();
        } catch (Exception $e) {
            $company = Company::factory()->create();
        }

        try {
            $trashtype = TrashType::all()->random();
        } catch (Exception $e) {
            $trashtype = TrashType::factory()->create();
        }

        return [
            'company_id' => $company->id,
            'trash_type_id' => $trashtype->id,
            'name' => [
                'it' => $this->faker->sentence(3),
                'en' => $this->faker->sentence(3),
            ],
            'where' => [
                'it' => $this->faker->sentence(6),
                'en' => $this->faker->sentence(6),
            ],
            'notes' => [
                'it' => $this->faker->sentence(30),
                'en' => $this->faker->sentence(30),
            ],
            'pap' => $this->faker->boolean(),
            'delivery' => $this->faker->boolean(),
            'collection_center' => $this->faker->boolean(),
        ];
    }
}
