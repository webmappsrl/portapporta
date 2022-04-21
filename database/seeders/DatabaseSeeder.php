<?php

namespace Database\Seeders;

use App\Models\TrashType;
use App\Models\Waste;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            CompanySeeder::class,
            TrashTypeSeeder::class,
            WasteSeeder::class,
            WasteCollectionCenterSeeder::class,
            UserTypeSeeder::class,
        ]);
    }
}
