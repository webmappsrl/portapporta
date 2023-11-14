<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
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

        //create roles
        if (!Role::where('name', 'super_admin')->exists()) {
            $superAdminRole = Role::create(['name' => 'super_admin']);
        } else {
            $superAdminRole = Role::where('name', 'super_admin')->first();
        }
        if (!Role::where('name', 'company_admin')->exists()) {
            $companyAdminRole = Role::create(['name' => 'company_admin']);
        } else {
            $companyAdminRole = Role::where('name', 'company_admin')->first();
        }
        if (!Role::where('name', 'contributor')->exists()) {
            $contributorRole = Role::create(['name' => 'contributor']);
        } else {
            $contributorRole = Role::where('name', 'contributor')->first();
        }

        //create permissions for super admin
        foreach (config('services.permissions.super_admin') as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }

            $superAdminRole->givePermissionTo($permission);
        }

        //create permissions for company admin
        foreach (config('services.permissions.company_admin') as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }

            $companyAdminRole->givePermissionTo($permission);
        }

        //create permissions for contributor
        foreach (config('services.permissions.contributor') as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }

            $contributorRole->givePermissionTo($permission);
        }
        //role to user contributor will be assigned in the import user command due to PHP memory limit:  app/Console/Commands/importUserFromApi.php
    }
}
