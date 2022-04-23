<?php

namespace Database\Factories;

use App\Models\Company;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WasteCollectionCenter>
 */
class WasteCollectionCenterFactory extends Factory
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
            'marker_color' => $this->faker->hexColor(),
            'marker_size' => $this->faker->randomElement(['small','middle','large']),
            'website' => $this->faker->url(),
            'picture_url' => $this->faker->url(),
            'name' => [
                'it' => $this->faker->sentence(3),
                'en' => $this->faker->sentence(3),
            ],
            'orario' => [
                'it' => $this->faker->sentence(3),
                'en' => $this->faker->sentence(3),
            ],
            'description' => [
                'it' => $this->faker->sentence(30),
                'en' => $this->faker->sentence(30),
            ],
            'geometry' => DB::select("SELECT ST_GeomFromText('POINT(10 45)') as g")[0]->g,
        ];
    }
}
