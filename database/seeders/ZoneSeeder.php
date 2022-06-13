<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Zone;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
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
                Zone::factory()->create([
                    'label' => 'Zona: '.$faker->name()." ({$company->name})",
                    'company_id' => $company->id]);
                }
        }
    }
}
