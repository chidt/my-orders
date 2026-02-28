<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Artisan::call('vnzone:import');
        // Always seed roles and permissions first
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // Seed users only in development or when explicitly requested
        // In production, you can run: php artisan db:seed --class=UserSeeder
        if (app()->environment('local', 'testing')) {
            $this->call([
                UserSeeder::class,
            ]);
        }

    }
}
