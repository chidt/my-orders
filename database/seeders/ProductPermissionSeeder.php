<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProductPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage_products' => 'Full product management - create, read, update, delete',
            'view_products' => 'View products only',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            Role::where('name', 'SiteAdmin')->first(),
            Role::where('name', 'Admin')->first(),
        ];

        foreach ($roles as $role) {
            if ($role) {
                $role->givePermissionTo([
                    'manage_products',
                    'view_products',
                ]);
            }
        }

        $this->command->info('Product permissions created and assigned to SiteAdmin role.');
    }
}

