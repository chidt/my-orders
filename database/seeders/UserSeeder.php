<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user (check if exists first)
        $adminUser = User::where('email', 'admin@system.com')->first();

        if (! $adminUser) {
            $adminUser = User::factory()->create([
                'name' => 'System Admin',
                'email' => 'admin@system.com',
                'phone_number' => '0123456789',
                'password' => 'password123',
            ]);
            $this->command->info('Admin user created: admin@system.com');
        } else {
            $this->command->info('Admin user already exists: admin@system.com');
        }

        // Assign role if not already assigned
        if (! $adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
            $this->command->info('Admin role assigned to user');
        }

        // Create site if not exists
        $site = Site::where('slug', 'demo-store')->first();

        if (! $site) {
            $site = Site::factory()->create([
                'name' => 'Demo Store',
                'slug' => 'demo-store',
                'description' => 'Demo store for testing',
            ]);
            $this->command->info('Demo store site created with slug: demo-store');
        } else {
            $this->command->info('Demo store site already exists');
        }

        // Create site admin user (check if exists first)
        $siteAdmin = User::where('email', 'siteadmin@demo-store.com')->first();

        if (! $siteAdmin) {
            $siteAdmin = User::factory()->create([
                'name' => 'Site Admin',
                'email' => 'siteadmin@demo-store.com',
                'phone_number' => '0987654321',
                'password' => 'password123',
                'site_id' => $site->id,
            ]);
            $this->command->info('Site admin user created: siteadmin@demo-store.com');
        } else {
            $this->command->info('Site admin user already exists: siteadmin@demo-store.com');
            // Update site_id if not set
            if (! $siteAdmin->site_id) {
                $siteAdmin->update(['site_id' => $site->id]);
            }
        }

        // Assign role if not already assigned
        if (! $siteAdmin->hasRole('SiteAdmin')) {
            $siteAdmin->assignRole('SiteAdmin');
            $this->command->info('SiteAdmin role assigned to user');
        }

        // Update site with owner if not set
        if (! $site->user_id) {
            $site->update(['user_id' => $siteAdmin->id]);
            $this->command->info('Site owner updated');
        }
    }
}
