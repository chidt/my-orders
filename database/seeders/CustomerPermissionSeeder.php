<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CustomerPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage_customers',
            'view_customers',
            'create_customers',
            'edit_customers',
            'delete_customers',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            Role::where('name', 'SiteAdmin')->first(),
            Role::where('name', 'Admin')->first(),
        ];

        foreach ($roles as $role) {
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        $this->command->info('Customer permissions created and assigned to roles.');
    }
}
