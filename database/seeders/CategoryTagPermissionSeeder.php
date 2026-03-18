<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CategoryTagPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Category permissions
        $categoryPermissions = [
            'manage_categories' => 'Full category management (create, update, delete)',
            'view_categories' => 'View categories',
            'create_categories' => 'Create new categories',
            'update_categories' => 'Update existing categories',
            'delete_categories' => 'Delete categories',
            'reorder_categories' => 'Reorder categories via drag & drop',
        ];

        // Tag permissions
        $tagPermissions = [
            'manage_tags' => 'Full tag management (create, update, delete)',
            'view_tags' => 'View tags',
            'create_tags' => 'Create new tags',
            'update_tags' => 'Update existing tags',
            'delete_tags' => 'Delete tags',
            'merge_tags' => 'Merge duplicate tags',
        ];

        // Create category permissions
        foreach ($categoryPermissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Create tag permissions
        foreach ($tagPermissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Assign permissions to SiteAdmin role
        $siteAdminRole = Role::firstOrCreate(['name' => 'SiteAdmin']);

        // Assign all category and tag permissions to SiteAdmin
        $allPermissions = array_merge(
            array_keys($categoryPermissions),
            array_keys($tagPermissions)
        );

        foreach ($allPermissions as $permission) {
            if (! $siteAdminRole->hasPermissionTo($permission)) {
                $siteAdminRole->givePermissionTo($permission);
            }
        }

        // Also assign to Admin role (super admin)
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        foreach ($allPermissions as $permission) {
            if (! $adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }
        }

        $this->command->info('Category and Tag permissions created and assigned to roles.');
    }
}
