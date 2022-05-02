<?php

namespace Database\Seeders;

use App\Models\TrashType;
use App\Models\Waste;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WasteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach(TrashType::all() as $trashtype) {
            Waste::factory()->create(['trash_type_id' => $trashtype->id, 'company_id' => $trashtype->company->id]);
            Waste::factory()->create(['trash_type_id' => $trashtype->id, 'company_id' => $trashtype->company->id]);
            Waste::factory()->create(['trash_type_id' => $trashtype->id, 'company_id' => $trashtype->company->id]);
            Waste::factory()->create(['trash_type_id' => $trashtype->id, 'company_id' => $trashtype->company->id]);
            Waste::factory()->create(['trash_type_id' => $trashtype->id, 'company_id' => $trashtype->company->id]);
            Waste::factory()->create(['trash_type_id' => $trashtype->id, 'company_id' => $trashtype->company->id]);
            Waste::factory()->create(['trash_type_id' => $trashtype->id, 'company_id' => $trashtype->company->id]);
            Waste::factory()->create(['trash_type_id' => $trashtype->id, 'company_id' => $trashtype->company->id]);
            Waste::factory()->create(['trash_type_id' => $trashtype->id, 'company_id' => $trashtype->company->id]);
            Waste::factory()->create(['trash_type_id' => $trashtype->id, 'company_id' => $trashtype->company->id]);
        }
    }
}
