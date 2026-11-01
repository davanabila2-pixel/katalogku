<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSoldProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_catalog_page_banner_removes_logo_and_links_to_sold_products_page(): void
    {
        $store = Store::create([
            'name' => 'Byvii Vintage Store',
            'slug' => 'byvii-vintage-store',
            'logo_path' => 'https://example.com/logo.png',
            'banner_path' => 'https://example.com/banner.jpg',
            'description' => 'Toko fashion vintage dan thrift terbaik.',
            'contact_information' => '081234567890',
            'status' => 'active',
        ]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Pakaian',
            'slug' => 'pakaian',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Jaket Jeans Vintage 90s',
            'slug' => 'jaket-jeans-vintage-90s',
            'selling_price' => 250000,
            'stock' => 15,
            'status' => 'active',
        ]);

        // Completed order with review for this store
        Order::create([
            'order_code' => 'ORD-BYVII-001',
            'store_id' => $store->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => $product->selling_price,
            'quantity' => 1,
            'total_price' => $product->selling_price,
            'buyer_name' => 'Ahmad Fauzi',
            'buyer_phone' => '081299998888',
            'buyer_address' => 'Jakarta Selatan',
            'status' => 'completed',
            'rating' => 5,
            'buyer_review' => 'Jaketnya sangat bagus dan otentik!',
            'reviewed_at' => now(),
        ]);

        $response = $this->get($store->url());

        $response->assertOk();
        // Logo is removed from the store hero
        $response->assertDontSee('store-hero-logo', false);

        // Action buttons exist in the hero
        $response->assertSee('Lihat Selengkapnya', false);
        $response->assertSee('Produk Terjual', false);
        $response->assertSee('btn-hero-explore', false);
        $response->assertSee('btn-hero-sold', false);
        $response->assertSee(route('store.sold', $store->slug), false);
        $response->assertSee('katalog-produk', false);
    }

    public function test_dedicated_sold_products_page_renders_completed_orders_and_buyer_reviews(): void
    {
        $store = Store::create([
            'name' => 'Byvii Fashion Store',
            'slug' => 'byvii-fashion-store',
            'status' => 'active',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Sepatu Boots Kulit Vintage',
            'slug' => 'sepatu-boots-kulit-vintage',
            'selling_price' => 450000,
            'stock' => 8,
            'status' => 'active',
        ]);

        Order::create([
            'order_code' => 'ORD-BYVII-002',
            'store_id' => $store->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => $product->selling_price,
            'quantity' => 2,
            'total_price' => 900000,
            'buyer_name' => 'Budi Santoso',
            'buyer_phone' => '081211112222',
            'buyer_address' => 'Surabaya',
            'status' => 'completed',
            'rating' => 5,
            'buyer_review' => 'Boots sangat nyaman dan kulit aslinya mantap!',
            'reviewed_at' => now(),
        ]);

        $response = $this->get(route('store.sold', $store->slug));

        $response->assertOk();
        $response->assertSeeText('Produk Terjual & Ulasan Pembeli', false);
        $response->assertSee('Sepatu Boots Kulit Vintage');
        $response->assertSee('Budi S.');
        $response->assertSee('Boots sangat nyaman dan kulit aslinya mantap!');
        $response->assertSee('Pembeli Terverifikasi');
        $response->assertSee('Terjual 2x');
        $response->assertSee('Kembali ke Katalog Toko');
    }

    public function test_sold_products_page_only_shows_completed_orders_of_its_own_store(): void
    {
        $storeA = Store::create([
            'name' => 'Toko Sepatu A',
            'slug' => 'toko-sepatu-a',
            'status' => 'active',
        ]);

        $storeB = Store::create([
            'name' => 'Toko Tas B',
            'slug' => 'toko-tas-b',
            'status' => 'active',
        ]);

        $prodA = Product::create([
            'store_id' => $storeA->id,
            'name' => 'Sepatu Sneakers A',
            'slug' => 'sepatu-sneakers-a',
            'selling_price' => 300000,
            'stock' => 5,
            'status' => 'active',
        ]);

        $prodB = Product::create([
            'store_id' => $storeB->id,
            'name' => 'Tas Ransel Kulit B',
            'slug' => 'tas-ransel-kulit-b',
            'selling_price' => 450000,
            'stock' => 5,
            'status' => 'active',
        ]);

        Order::create([
            'order_code' => 'ORD-A-001',
            'store_id' => $storeA->id,
            'product_id' => $prodA->id,
            'product_name' => $prodA->name,
            'product_price' => $prodA->selling_price,
            'quantity' => 1,
            'total_price' => $prodA->selling_price,
            'buyer_name' => 'Pembeli Toko A',
            'buyer_phone' => '081200000001',
            'buyer_address' => 'Jakarta',
            'status' => 'completed',
            'rating' => 5,
            'buyer_review' => 'Ulasan khusus Toko A',
            'reviewed_at' => now(),
        ]);

        Order::create([
            'order_code' => 'ORD-B-001',
            'store_id' => $storeB->id,
            'product_id' => $prodB->id,
            'product_name' => $prodB->name,
            'product_price' => $prodB->selling_price,
            'quantity' => 1,
            'total_price' => $prodB->selling_price,
            'buyer_name' => 'Pembeli Toko B',
            'buyer_phone' => '081200000002',
            'buyer_address' => 'Surabaya',
            'status' => 'completed',
            'rating' => 5,
            'buyer_review' => 'Ulasan khusus Toko B',
            'reviewed_at' => now(),
        ]);

        // When viewing Toko A's sold page
        $resA = $this->get(route('store.sold', $storeA->slug));
        $resA->assertOk();
        $resA->assertSee('Sepatu Sneakers A');
        $resA->assertSee('Ulasan khusus Toko A');
        $resA->assertDontSee('Tas Ransel Kulit B');
        $resA->assertDontSee('Ulasan khusus Toko B');

        // When viewing Toko B's sold page
        $resB = $this->get(route('store.sold', $storeB->slug));
        $resB->assertOk();
        $resB->assertSee('Tas Ransel Kulit B');
        $resB->assertSee('Ulasan khusus Toko B');
        $resB->assertDontSee('Sepatu Sneakers A');
        $resB->assertDontSee('Ulasan khusus Toko A');
    }
}
