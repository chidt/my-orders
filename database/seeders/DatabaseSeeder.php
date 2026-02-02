<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Always seed roles and permissions first
        $this->call([
            RoleSeeder::class,
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
