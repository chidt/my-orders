<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Province;
use App\Models\Site;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting CustomerSeeder...');

        // Check if required data exists
        if (Province::count() === 0) {
            $this->command->error('❌ No provinces found. Please run the province import command first (e.g., php artisan vnzone:import).');

            return;
        }

        if (Ward::count() === 0) {
            $this->command->error('❌ No wards found. Please run the ward import command first (e.g., php artisan vnzone:import).');

            return;
        }

        $this->command->info('✅ Found '.Province::count().' provinces and '.Ward::count().' wards');

        // Create sites (they don't exist from import)
        $this->command->info('Creating sites...');
        $sites = Site::factory(5)->create();
        $this->command->info('✅ Created '.$sites->count().' sites');

        // Create customers
        $this->command->info('Creating customers...');
        $customers = collect();

        for ($i = 0; $i < 20; $i++) {
            $customer = Customer::factory()->create([
                'site_id' => $sites->random()->id,
            ]);
            $customers->push($customer);

            // Create associated user
            User::factory()->create([
                'name' => $customer->name,
                'email' => $customer->email, // Use same email as customer
                'customer_id' => $customer->id,
                'site_id' => $customer->site_id,
                'password' => Hash::make('password'),
            ]);

            // Create addresses for each customer (using existing wards)
            $addressCount = rand(1, 3);
            for ($j = 0; $j < $addressCount; $j++) {
                try {
                    Address::factory()->forCustomer($customer)->create();
                } catch (\RuntimeException $e) {
                    $this->command->error('❌ Failed to create address: '.$e->getMessage());

                    continue;
                }
            }

            if (($i + 1) % 5 === 0) {
                $this->command->info('  Created '.($i + 1).' customers...');
            }
        }

        $this->command->info('✅ Created '.$customers->count().' customers with users and addresses');

        // Summary statistics
        $totalAddresses = Address::count();
        $totalUsers = User::whereNotNull('customer_id')->count();

        $this->command->info('📊 Summary:');
        $this->command->info('  - Customers: '.$customers->count());
        $this->command->info('  - Users with customers: '.$totalUsers);
        $this->command->info('  - Addresses: '.$totalAddresses);
        $this->command->info('  - Sites: '.$sites->count());

        // Validate data integrity
        $this->validateDataIntegrity();
    }

    /**
     * Validate the integrity of created data.
     */
    private function validateDataIntegrity(): void
    {
        $this->command->info('🔍 Validating data integrity...');

        // Check all customers have sites
        $customersWithoutSite = Customer::whereNull('site_id')->count();
        if ($customersWithoutSite > 0) {
            $this->command->error('❌ Found '.$customersWithoutSite.' customers without site_id');
        } else {
            $this->command->info('✅ All customers have site_id');
        }

        // Check all users have valid customer references
        $usersWithInvalidCustomer = User::whereNotNull('customer_id')
            ->whereNotIn('customer_id', Customer::pluck('id'))
            ->count();
        if ($usersWithInvalidCustomer > 0) {
            $this->command->error('❌ Found '.$usersWithInvalidCustomer.' users with invalid customer_id');
        } else {
            $this->command->info('✅ All user customer references are valid');
        }

        // Check all addresses have valid ward references
        $addressesWithInvalidWard = Address::whereNotIn('ward_id', Ward::pluck('id'))->count();
        if ($addressesWithInvalidWard > 0) {
            $this->command->error('❌ Found '.$addressesWithInvalidWard.' addresses with invalid ward_id');
        } else {
            $this->command->info('✅ All address ward references are valid');
        }

        // Check all addresses have valid addressable references
        $addressesWithInvalidAddressable = Address::where(function ($query) {
            $query->where('addressable_type', Customer::class)
                ->whereNotIn('addressable_id', Customer::pluck('id'))
                ->orWhere('addressable_type', User::class)
                ->whereNotIn('addressable_id', User::pluck('id'));
        })->count();
        if ($addressesWithInvalidAddressable > 0) {
            $this->command->error('❌ Found '.$addressesWithInvalidAddressable.' addresses with invalid addressable references');
        } else {
            $this->command->info('✅ All address addressable references are valid');
        }

        $this->command->info('✅ Data integrity validation completed');
    }
}
