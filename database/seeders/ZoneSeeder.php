<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Zone;
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
        foreach(Company::all() as $company) {
            Zone::factory()->create(['company_id' => $company->id]);
            Zone::factory()->create(['company_id' => $company->id]);
            Zone::factory()->create(['company_id' => $company->id]);
            Zone::factory()->create(['company_id' => $company->id]);
            Zone::factory()->create(['company_id' => $company->id]);
        }
    }
}
