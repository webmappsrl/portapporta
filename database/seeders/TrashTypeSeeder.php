<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\TrashType;
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
        foreach(Company::all() as $company) {
            TrashType::factory()->create(['company_id' => $company->id]);
            TrashType::factory()->create(['company_id' => $company->id]);
            TrashType::factory()->create(['company_id' => $company->id]);
            TrashType::factory()->create(['company_id' => $company->id]);
            TrashType::factory()->create(['company_id' => $company->id]);
        }
    }
}
