<?php

namespace Database\Factories;

use App\Models\Company;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Zone>
 */
class ZoneFactory extends Factory
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
            'comune' => $this->faker->city(),
            'label' => $this->faker->sentence(3),
            'url' => $this->faker->url(),
            'geometry' => DB::select("(SELECT ST_GeomFromText('MULTIPOLYGON(((10 45, 11 45, 11 46, 11 46, 10 45)))')as g)")[0]->g,
        ];
    }
}
