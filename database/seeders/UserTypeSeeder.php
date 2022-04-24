<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\UserType;
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
        foreach(Company::all() as $company) {
            UserType::factory()->create(['company_id' => $company->id]);
            UserType::factory()->create(['company_id' => $company->id]);
            UserType::factory()->create(['company_id' => $company->id]);
            UserType::factory()->create(['company_id' => $company->id]);
            UserType::factory()->create(['company_id' => $company->id]);
        }
    }
}
