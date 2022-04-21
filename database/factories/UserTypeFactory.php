<?php

namespace Database\Factories;

use App\Models\Company;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserType>
 */
class UserTypeFactory extends Factory
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
        ];
    }
}
