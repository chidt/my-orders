<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles (using firstOrCreate to prevent duplicates)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $siteAdminRole = Role::firstOrCreate(['name' => 'SiteAdmin']);

        // Create permissions (using firstOrCreate to prevent duplicates)
        $permissions = [
            'manage-users',
            'manage-sites',
            'view-admin-dashboard',
            'manage-own-site',
            'view-site-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $adminRole->givePermissionTo([
            'manage-users',
            'manage-sites',
            'view-admin-dashboard',
        ]);

        $siteAdminRole->givePermissionTo([
            'manage-own-site',
            'view-site-dashboard',
        ]);

        $this->command->info('Roles and permissions created successfully.');
    }
}
