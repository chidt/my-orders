<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        // Create roles (using firstOrCreate to prevent duplicates)
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $siteAdminRole = Role::firstOrCreate(['name' => 'SiteAdmin']);
        // Create permissions
        $permissions = [
            // Dashboard access
            'view_admin_dashboard',
            'view_site_dashboard',

            // Permission management
            'view_permissions',
            'create_permissions',
            'edit_permissions',
            'delete_permissions',
            'manage_permissions',

            // Role management
            'view_roles',
            'create_roles',
            'edit_roles',
            'delete_roles',
            'manage_roles',

            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_users',
            'manage_site_users',
            'view_site_users',
            'create_site_users',
            'edit_site_users',
            'delete_site_users',

            // Site management
            'view_sites',
            'create_sites',
            'edit_sites',
            'delete_sites',
            'manage_sites',
            'manage_own_site',

            // Warehouse management
            'manage_warehouses',
            'create_warehouses',
            'view_warehouses',
            'edit_warehouses',
            'delete_warehouses',

            // Warehouse location management
            'manage_warehouse_locations',
            'create_warehouse_locations',
            'view_warehouse_locations',
            'edit_warehouse_locations',
            'delete_warehouse_locations',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign all permissions to admin role
        $adminRole->syncPermissions($permissions);
        // remove permission manage_own_site for admin role
        $adminRole->revokePermissionTo('manage_own_site');

        $siteAdminRole->givePermissionTo([
            'manage_own_site',
            'view_site_dashboard',
            'manage_site_users',
            'manage_warehouses',
            'create_warehouses',
            'view_warehouses',
            'edit_warehouses',
            'delete_warehouses',
            'manage_warehouse_locations',
            'create_warehouse_locations',
            'view_warehouse_locations',
            'edit_warehouse_locations',
            'delete_warehouse_locations',
        ]);

        $this->command->info('Roles, permissions, and admin user role assignment completed.');

    }
}
