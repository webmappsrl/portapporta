<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Company::factory()->create(['name' => 'ersu','user_id' => User::where('email','lucaleon@gmail.com')->first()->id]);
        Company::factory()->create(['name' => 'asmiu']);
        Company::factory()->create(['name' => 'rea']);
        Company::factory()->create(['name' => 'esa','user_id' => User::where('email','s.guasti@esaspa.it')->first()->id]);
        Company::factory()->create(['name' => 'sea']);
    }
}
