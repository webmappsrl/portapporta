<?php

namespace Database\Seeders;

use App\Models\Waste;
use App\Models\TrashType;
use Illuminate\Database\Seeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

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
            RoleAndPermissionSeeder::class,
            CompanySeeder::class,
            TrashTypeSeeder::class,
            WasteSeeder::class,
            WasteCollectionCenterSeeder::class,
            UserTypeSeeder::class,
            ZoneSeeder::class,
            ManyToManySeeder::class,
            CalendarSeeder::class,
            CalendarItemSeeder::class,
            TicketSeeder::class,

        ]);
    }
}
