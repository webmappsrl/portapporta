<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'configTs' => $this->faker->regexify('[A-Za-z0-9]{20}').'.ts',
            'configJson' => $this->faker->regexify('[A-Za-z0-9]{20}').'.json',
            'configXMLID' => 'it.webmapp.'.$this->faker->regexify('[A-Za-z0-9]{10}'),
            'description' => $this->faker->word(40),
            'version' => '1.'.$this->faker->numberBetween(10,20),
            'icon' => $this->faker->regexify('[A-Za-z0-9]{20}').'.png',
            'splash' => $this->faker->regexify('[A-Za-z0-9]{20}').'.png',
        ];
    }
}
