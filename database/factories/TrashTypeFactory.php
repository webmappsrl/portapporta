<?php

namespace Database\Factories;

use App\Models\Company;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrashType>
 */
class TrashTypeFactory extends Factory
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
        return [
            'company_id' => $company->id,
            'slug' => $this->faker->slug(),
            'color' => $this->faker->rgbColor(),
            'name' => [
                'it' => $this->faker->sentence(3),
                'en' => $this->faker->sentence(3),
            ],
            'description' => [
                'it' => $this->faker->sentence(30),
                'en' => $this->faker->sentence(30),
            ],
            'where' => [
                'it' => $this->faker->sentence(6),
                'en' => $this->faker->sentence(6),
            ],
            'howto' => [
                'it' => $this->faker->sentence(30),
                'en' => $this->faker->sentence(30),
            ]

        ];
    }
}
