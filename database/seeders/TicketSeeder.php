<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
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
        foreach(Company::all() as $company) {
            $user = User::factory()->create();
            $user->email="{$company->name}.{$user->id}@email.com";
            $user->password=bcrypt('webmapp');
            $user->save();

            for ($i=0;$i < 100; $i++) { 
                $t = Ticket::factory()->create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                ]);
                $t->created_at = Carbon::today()->subDays(rand(0, 365));
                $t->save();
            }
        }
    }
}
