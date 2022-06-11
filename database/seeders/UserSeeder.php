<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->create([
            'name' => 'amministratore webmapp',
            'email' => 'admin@webmapp.it',
            'password' => bcrypt('webmapp'),
        ])->markEmailAsVerified();
        User::factory()->create([
            'name' => 'Sara Guasti',
            'email' => 's.guasti@esaspa.it',
            'password' => bcrypt('webmapp'),
        ])->markEmailAsVerified();
        User::factory()->create([
            'name' => 'Luca Leonardi',
            'email' => 'lucaleon@gmail.com',
            'password' => bcrypt('webmapp'),
        ])->markEmailAsVerified();

        User::factory(100)->create();
    }
}
