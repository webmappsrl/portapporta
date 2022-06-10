<?php

namespace Database\Seeders;

use App\Models\Calendar;
use App\Models\Company;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CalendarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $year = date('Y');
        $winter_start=Carbon::parse("$year-01-01 00:00:00");
        $winter_stop=Carbon::parse("$year-06-30 11:59:59");
        $summer_start=Carbon::parse("$year-07-01 00:00:00");
        $summer_stop=Carbon::parse("$year-12-31 11:59:59");
        foreach(Company::all() as $company) {
            foreach($company->zones as $zone) {
            // winter calendar 01/01/this year -> 30/06/this-year
            $data_winter = [
                'name' => "WINTER Calendar $year for Company {$company->name} / Zone: {$zone->label}",
                'start_date' => $winter_start,
                'stop_date' => $winter_stop,
                'company_id' => $company->id,
                'zone_id' => $zone->id,
            ];
            Calendar::factory()->create($data_winter);
            // summer calendar
            $data_summer = [
                'name' => "SUMMER Calendar $year for Company {$company->name} / Zone: {$zone->label}",
                'start_date' => $summer_start,
                'stop_date' => $summer_stop,
                'company_id' => $company->id,
                'zone_id' => $zone->id,
            ];
            Calendar::factory()->create($data_summer);
            }
        }
    }
}
