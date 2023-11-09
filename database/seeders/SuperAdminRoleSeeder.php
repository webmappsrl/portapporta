<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SuperAdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        //assign role to user super admin
        $superAdmin = User::where('email', 'admin@webmapp.it')->first();
        $superAdminRole = \Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
        $superAdmin->assignRole($superAdminRole);
    }
}
