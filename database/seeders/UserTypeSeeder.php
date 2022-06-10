<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\UserType;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $faker = Factory::create();

        foreach(Company::all() as $company) {
            for ($i=0; $i <5 ; $i++) { 
                UserType::factory()->create([
                    'label' => [
                        'it' => 'UserType: '.$faker->name()." ({$company->name}) / IT",
                        'en' => 'UserType: '.$faker->name()." ({$company->name}) / EN",
                    ],
                    'company_id' => $company->id]);
                }
        }
    }
}
