<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk pengembangan lokal. JANGAN dijalankan di production.
 * php artisan db:seed --class=Database\\Seeders\\DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::where('role', 'superadmin')->firstOrFail();

        foreach (['Toko 1', 'Toko 2'] as $name) {
            $store = Store::firstOrCreate(['slug' => str($name)->slug()->toString()], [
                'name' => $name,
                'description' => "Katalog demo {$name}.",
                'contact_information' => '081234567890',
                'status' => 'active',
            ]);
            if (empty($store->contact_information)) {
                $store->update(['contact_information' => '081234567890']);
            }

            $admin = User::firstOrCreate(['email' => str($name)->slug().'@example.com'], [
                'name' => "Admin {$name}",
                'password' => 'password',
                'role' => 'admin',
                'is_active' => true,
            ]);
            $store->users()->syncWithoutDetaching([$admin->id => ['is_primary' => true]]);
            $store->users()->syncWithoutDetaching([$superadmin->id => ['is_primary' => false]]);
        }

        $store1 = Store::where('slug', 'toko-1')->firstOrFail();
        $atasan = Category::firstOrCreate(['store_id' => $store1->id, 'slug' => 'atasan'], ['name' => 'Atasan']);
        $kaos = Category::firstOrCreate(['store_id' => $store1->id, 'slug' => 'kaos'], ['name' => 'Kaos', 'parent_id' => $atasan->id]);

        $product = Product::firstOrCreate(['store_id' => $store1->id, 'slug' => 'kaos-vintage'], [
            'category_id' => $kaos->id,
            'name' => 'Kaos Vintage',
            'selling_price' => 85000,
            'status' => 'active',
        ]);

        if ($product->batchItems()->count() === 0) {
            app(InventoryService::class)->createBatch([
                'store_id' => $store1->id,
                'batch_code' => 'BATCH-DEMO-1',
                'purchase_date' => now()->toDateString(),
                'supplier_name' => 'Supplier Demo',
                'additional_cost' => 10000,
            ], [
                ['product_id' => $product->id, 'quantity' => 50, 'unit_purchase_price' => 20000],
            ], $superadmin);
        }

        $this->command->info('DemoSeeder selesai: /toko-1, /toko-2');
    }
}
