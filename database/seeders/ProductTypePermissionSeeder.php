<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProductTypePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for product type management
        $permissions = [
            'manage_product_types' => 'Full product type management - create, read, update, delete',
            'view_product_types' => 'View product types only',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // Assign permissions to SiteAdmin role
        $siteAdminRole = Role::where('name', 'SiteAdmin')->first();
        if ($siteAdminRole) {
            $siteAdminRole->givePermissionTo([
                'manage_product_types',
                'view_product_types',
            ]);
        }

        $this->command->info('Product type permissions created and assigned to SiteAdmin role.');
    }
}
