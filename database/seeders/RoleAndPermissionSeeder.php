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

        //create permissions
        if (!Permission::where('name', 'manage_users')->exists()) {
            Permission::create(['name' => 'manage_users']);
        }
        if (!Permission::where('name', 'manage_roles')->exists()) {
            Permission::create(['name' => 'manage_roles']);
        }
        if (!Permission::where('name', 'manage_companies')->exists()) {
            Permission::create(['name' => 'manage_companies']);
        }
        if (!Permission::where('name', 'manage_trash_types')->exists()) {
            Permission::create(['name' => 'manage_trash_types']);
        }
        if (!Permission::where('name', 'manage_wastes')->exists()) {
            Permission::create(['name' => 'manage_wastes']);
        }
        if (!Permission::where('name', 'manage_waste_collection_centers')->exists()) {
            Permission::create(['name' => 'manage_waste_collection_centers']);
        }
        if (!Permission::where('name', 'manage_user_types')->exists()) {
            Permission::create(['name' => 'manage_user_types']);
        }
        if (!Permission::where('name', 'manage_zones')->exists()) {
            Permission::create(['name' => 'manage_zones']);
        }
        if (!Permission::where('name', 'manage_calendars')->exists()) {
            Permission::create(['name' => 'manage_calendars']);
        }
        if (!Permission::where('name', 'manage_tickets')->exists()) {
            Permission::create(['name' => 'manage_tickets']);
        }
        if (!Permission::where('name', 'manage_roles_and_permissions')->exists()) {
            Permission::create(['name' => 'manage_roles_and_permissions']);
        }
        if (!Permission::where('name', 'no_permissions')->exists()) {
            Permission::create(['name' => 'no_permissions']);
        }

        //assign permissions to roles
        $superAdminRole->givePermissionTo(Permission::all());
        $companyAdminRole->givePermissionTo(config('services.permissions.company_admin'));
        $contributorRole->givePermissionTo(config('services.permissions.contributor'));

        //assign roles to users
        $superAdmin = User::where('email', 'admin@webmapp.it')->first();
        $superAdmin->assignRole($superAdminRole);

        $companyAdmins = User::where('app_company_id', '!=', null)->get();

        foreach ($companyAdmins as $companyAdmin) {
            $companyAdmin->assignRole($companyAdminRole);
        }

        $contributors = User::where('app_company_id', null)->get();

        foreach ($contributors as $contributor) {
            $contributor->assignRole($contributorRole);
        }
    }
}
