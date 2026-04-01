<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductItem;
use App\Models\Site;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🛒 Starting Order seeding...');

        // Get sites that have ProductItems
        $sitesWithProducts = Site::where(function ($query) {
            $query->whereExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('product_items')
                    ->whereColumn('product_items.site_id', 'sites.id');
            });
        })->get();

        if ($sitesWithProducts->isEmpty()) {
            $this->command->warn('⚠️ No sites found with product items. Please run ProductSeeder first.');

            return;
        }

        foreach ($sitesWithProducts as $site) {
            $this->seedOrdersForSite($site);
        }

        $this->command->info('✅ Order seeding completed!');
    }

    private function seedOrdersForSite(Site $site): void
    {
        $this->command->info("🏪 Seeding orders for site: {$site->name}");

        $customers = $site->customers()->with('addresses')->get();
        $productItems = ProductItem::where('site_id', $site->id)->with('product')->get();

        if ($customers->isEmpty()) {
            $this->command->warn("   ⚠️ No customers found for site {$site->name}. Using customers from other sites...");
            $customers = Customer::with('addresses')->limit(5)->get();

            if ($customers->isEmpty()) {
                $this->command->warn('   ⚠️ No customers found in system. Skipping...');

                return;
            }
        }

        if ($productItems->isEmpty()) {
            $this->command->warn("   ⚠️ No product items found for site {$site->name}. Skipping...");

            return;
        }

        // Create 20 orders for this site
        DB::transaction(function () use ($site, $customers, $productItems) {
            for ($i = 1; $i <= 20; $i++) {
                $this->createOrderWithDetails($site, $customers, $productItems, $i);
            }
        });

        $this->command->info("   ✅ Created 20 orders for site: {$site->name}");
    }

    private function createOrderWithDetails(Site $site, $customers, $productItems, int $orderNumber): void
    {
        $customer = $customers->random();
        $shippingAddress = $customer->addresses()->where('is_default', true)->first()
            ?? $customer->addresses()->first();

        // Create the order
        $order = Order::create([
            'payment_status' => 1, // Unpaid
            'order_number' => $this->generateOrderNumber($site, $orderNumber),
            'order_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'customer_type' => $customer->type?->value ?? (int) $customer->type,
            'status' => OrderStatus::New->value, // New status as requested
            'shipping_payer' => fake()->numberBetween(1, 2), // 1: Customer pays, 2: Store pays
            'phone' => $customer->phone,
            'shipping_note' => fake()->optional(0.3)->sentence(),
            'order_note' => fake()->optional(0.4)->sentence(),
            'sale_channel' => fake()->numberBetween(1, 3), // 1: Online, 2: Phone, 3: In-store
            'shipping_address_id' => $shippingAddress?->id,
            'customer_id' => $customer->id,
            'site_id' => $site->id,
        ]);

        // Create 5 order details for this order
        $selectedProductItems = $productItems->random(5);

        foreach ($selectedProductItems as $productItem) {
            $qty = fake()->numberBetween(1, 3);
            $price = (float) $productItem->price;
            $discount = fake()->optional(0.2)->randomFloat(2, 0, $price * 0.1); // 20% chance of discount up to 10%
            $discountAmount = $discount ?? 0;
            $additionPrice = fake()->optional(0.1)->randomFloat(2, 0, 50000); // 10% chance of addition price
            $additionAmount = $additionPrice ?? 0;
            $total = ($price * $qty) - $discountAmount + $additionAmount;

            OrderDetail::create([
                'payment_status' => 1, // Unpaid
                'payment_request_detail_id' => null,
                'status' => OrderStatus::New->value, // New status as requested
                'fulfillment_status' => 0, // Not fulfilled
                'qty' => $qty,
                'price' => $price,
                'discount' => $discountAmount,
                'addition_price' => $additionAmount,
                'total' => $total,
                'note' => fake()->optional(0.3)->sentence(),
                'product_item_id' => $productItem->id,
                'order_id' => $order->id,
                'site_id' => $site->id,
                'purchase_request_detail_id' => null,
                'order_date' => $order->order_date,
                'expected_fulfillment_date' => fake()->optional(0.6)->dateTimeBetween('now', '+7 days'),
                'extra_attributes' => null,
            ]);
        }

        $this->command->info("     📄 Created order: {$order->order_number} with 5 details for customer: {$customer->name}");
    }

    private function generateOrderNumber(Site $site, int $orderNumber): string
    {
        $siteCode = strtoupper(substr($site->slug, 0, 3));
        $dateString = now()->format('Ymd');
        $orderSequence = str_pad($orderNumber, 3, '0', STR_PAD_LEFT);

        return "ORD-{$siteCode}-{$dateString}-{$orderSequence}";
    }
}
