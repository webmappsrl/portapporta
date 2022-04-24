<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WasteCollectionCenter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WasteCollectionCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach(Company::all() as $company) {
            WasteCollectionCenter::factory()->create(['company_id' => $company->id]);
            WasteCollectionCenter::factory()->create(['company_id' => $company->id]);
            WasteCollectionCenter::factory()->create(['company_id' => $company->id]);
            WasteCollectionCenter::factory()->create(['company_id' => $company->id]);
            WasteCollectionCenter::factory()->create(['company_id' => $company->id]);
        }
    }
}
