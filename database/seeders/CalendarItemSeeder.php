<?php

namespace Database\Seeders;

use App\Models\Calendar;
use App\Models\CalendarItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CalendarItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach(Calendar::all() as $calendar) {
            for ($i=0; $i < 7; $i++) { 
                CalendarItem::factory()->create([
                    'start_time' => '7:00',
                    'stop_time' => '11:00',
                    'day_of_week' => $i,
                    'frequency' => 'weekly',
                    'calendar_id' => $calendar->id,
                ]);
            }
        }

        // ELEGANT RANDOM ARRAY VALUES: array_rand (array_flip($a),rand(2,count($a)))
        foreach(CalendarItem::all() as $item) {
            $trash_types = $item->calendar->company->trashTypes->pluck('id')->toArray();
            $item->trashTypes()->attach(array_rand (array_flip($trash_types),rand(1,count($trash_types))));
        }
        
    }
}
