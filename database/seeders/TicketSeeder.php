<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $numberOfTikets = 10;
        foreach (range(1, $numberOfTikets) as $index) {
            Ticket::factory()->create(['user_id' => 1]);
        }
    }
}
