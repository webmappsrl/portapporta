<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\TrashType;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrashTypeSeeder extends Seeder
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
                TrashType::factory()->create([
                    'name' => [
                        'it' =>'TrashType: '.$faker->name()." ({$company->name}) IT",
                        'en' =>'TrashType: '.$faker->name()." ({$company->name}) EN",
                    ],
                    'company_id' => $company->id]);
                }
        }

    }
}
