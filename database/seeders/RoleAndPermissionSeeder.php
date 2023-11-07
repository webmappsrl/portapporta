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

        //reset all roles for the users
        User::all()->each(function ($user) {
            $user->syncRoles([]);
        });

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
        //reset permissions
        $superAdminRole->revokePermissionTo(Permission::all());
        foreach (config('services.permissions.super_admin') as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }

            $superAdminRole->givePermissionTo($permission);
        }
        //assign role to user super admin
        $superAdmin = User::where('email', 'admin@webmapp.it')->first();
        $superAdmin->assignRole($superAdminRole);

        //create permissions for company admin
        //reset permissions
        $companyAdminRole->revokePermissionTo(Permission::all());
        foreach (config('services.permissions.company_admin') as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }

            $companyAdminRole->givePermissionTo($permission);
        }
        //assign role to user company admin
        $companyAdmins = User::where('company_id', '!=', null)->get();

        foreach ($companyAdmins as $companyAdmin) {
            $companyAdmin->assignRole($companyAdminRole);
        }

        //create permissions for contributor
        //reset permissions
        $contributorRole->revokePermissionTo(Permission::all());
        foreach (config('services.permissions.contributor') as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission]);
            }

            $contributorRole->givePermissionTo($permission);
        }

        //assign role to user contributor
        $contributors = User::where('app_company_id', null)->get();

        foreach ($contributors as $contributor) {
            $contributor->assignRole($contributorRole);
        }
    }
}
