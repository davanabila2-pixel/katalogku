<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\BiteshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductWeightAndBundleTest extends TestCase
{
    use RefreshDatabase;

    protected function setupStoreAndProducts(): array
    {
        $admin = User::create([
            'name' => 'Admin Toko',
            'email' => 'admin@toko.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $store = Store::create([
            'name' => 'Toko Fashion Store',
            'slug' => 'toko-fashion-store',
            'status' => 'active',
            'address' => 'Jl. Riau No. 10',
            'city' => 'Kota Bandung',
            'district' => 'Bandung Wetan',
            'province' => 'Jawa Barat',
            'postal_code' => '40115',
            'latitude' => -6.9024,
            'longitude' => 107.6186,
            'active_couriers' => ['jne', 'sicepat'],
        ]);

        $store->users()->attach($admin->id, ['is_primary' => true]);

        $kaos = Product::create([
            'store_id' => $store->id,
            'name' => 'Kaos Polos Cotton',
            'slug' => 'kaos-polos-cotton',
            'selling_price' => 75000,
            'weight' => 200, // 200 gram
            'stock' => 20,
            'status' => 'active',
        ]);

        $celana = Product::create([
            'store_id' => $store->id,
            'name' => 'Celana Chino Slim',
            'slug' => 'celana-chino-slim',
            'selling_price' => 150000,
            'weight' => 450, // 450 gram
            'stock' => 15,
            'status' => 'active',
        ]);

        $jaket = Product::create([
            'store_id' => $store->id,
            'name' => 'Jaket Parka Outdoor',
            'slug' => 'jaket-parka-outdoor',
            'selling_price' => 250000,
            'weight' => 800, // 800 gram
            'stock' => 8,
            'status' => 'active',
        ]);

        return [$admin, $store, $kaos, $celana, $jaket];
    }

    public function test_produk_satuan_memiliki_berat_dan_format_tampilan(): void
    {
        [$admin, $store, $kaos, $celana, $jaket] = $this->setupStoreAndProducts();

        $this->assertEquals(200, $kaos->effectiveWeight());
        $this->assertEquals('200 gr', $kaos->formattedWeight());

        $this->assertEquals(450, $celana->effectiveWeight());
        $this->assertEquals('450 gr', $celana->formattedWeight());

        $this->assertEquals(800, $jaket->effectiveWeight());
        $this->assertEquals('800 gr', $jaket->formattedWeight());

        // Default 500 gram jika weight null / 0
        $defaultProd = Product::create([
            'store_id' => $store->id,
            'name' => 'Aksesoris Topi',
            'slug' => 'aksesoris-topi',
            'selling_price' => 45000,
            'stock' => 10,
            'status' => 'active',
        ]);

        $this->assertEquals(500, $defaultProd->effectiveWeight());
        $this->assertEquals('500 gr', $defaultProd->formattedWeight());
    }

    public function test_berat_produk_bundle_dihitung_otomatis_dari_akumulasi_komponen(): void
    {
        [$admin, $store, $kaos, $celana, $jaket] = $this->setupStoreAndProducts();

        // 1 Paket Casual Set: 1 Kaos (200 gr) + 1 Celana (450 gr) = 650 gr
        $bundleCasual = Product::create([
            'store_id' => $store->id,
            'name' => 'Paket Casual Set (Kaos + Celana)',
            'slug' => 'paket-casual-set',
            'selling_price' => 210000,
            'is_bundle' => true,
            'weight' => null, // Tidak di-override manual
            'status' => 'active',
        ]);

        $bundleCasual->bundleItems()->attach([
            $kaos->id => ['quantity' => 1],
            $celana->id => ['quantity' => 1],
        ]);

        $bundleCasual->load('bundleItems');

        $this->assertEquals(650, $bundleCasual->bundleTotalWeight());
        $this->assertEquals(650, $bundleCasual->effectiveWeight());
        $this->assertEquals('650 gr', $bundleCasual->formattedWeight());

        // Paket Adventure: 2 Kaos (200*2 = 400 gr) + 1 Jaket (800 gr) = 1.200 gr (1,2 kg)
        $bundleAdventure = Product::create([
            'store_id' => $store->id,
            'name' => 'Paket Adventure (2 Kaos + Jaket)',
            'slug' => 'paket-adventure',
            'selling_price' => 380000,
            'is_bundle' => true,
            'weight' => null,
            'status' => 'active',
        ]);

        $bundleAdventure->bundleItems()->attach([
            $kaos->id => ['quantity' => 2],
            $jaket->id => ['quantity' => 1],
        ]);

        $bundleAdventure->load('bundleItems');

        $this->assertEquals(1200, $bundleAdventure->bundleTotalWeight());
        $this->assertEquals(1200, $bundleAdventure->effectiveWeight());
        $this->assertEquals('1,2 kg', $bundleAdventure->formattedWeight());
    }

    public function test_berat_bundle_dapat_di_override_manual_dengan_berat_khusus(): void
    {
        [$admin, $store, $kaos, $celana] = $this->setupStoreAndProducts();

        // Total komponen: 200 + 450 = 650 gr, tetapi diberi kemasan gift box tebal menjadi 950 gr
        $bundleWithBox = Product::create([
            'store_id' => $store->id,
            'name' => 'Paket Gift Box Premium',
            'slug' => 'paket-gift-box-premium',
            'selling_price' => 250000,
            'is_bundle' => true,
            'weight' => 950, // Manual override
            'status' => 'active',
        ]);

        $bundleWithBox->bundleItems()->attach([
            $kaos->id => ['quantity' => 1],
            $celana->id => ['quantity' => 1],
        ]);

        $bundleWithBox->load('bundleItems');

        // bundleTotalWeight tetap mencatat berat murni isi komponen
        $this->assertEquals(650, $bundleWithBox->bundleTotalWeight());
        // effectiveWeight menggunakan override manual yang telah diisi admin
        $this->assertEquals(950, $bundleWithBox->effectiveWeight());
        $this->assertEquals('950 gr', $bundleWithBox->formattedWeight());
    }

    public function test_admin_dapat_input_berat_produk_via_form(): void
    {
        [$admin, $store] = $this->setupStoreAndProducts();

        $response = $this->actingAs($admin)->post(route('admin.products.store', $store), [
            'name' => 'Sepatu Sneakers Pria',
            'selling_price' => 350000,
            'weight' => 850,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.products.index', $store));

        $created = Product::where('store_id', $store->id)->where('name', 'Sepatu Sneakers Pria')->first();
        $this->assertNotNull($created);
        $this->assertEquals(850, $created->weight);
        $this->assertEquals('850 gr', $created->formattedWeight());

        // Update berat produk
        $updateResponse = $this->actingAs($admin)->put(route('admin.products.update', [$store, $created]), [
            'name' => 'Sepatu Sneakers Pria V2',
            'selling_price' => 380000,
            'weight' => 1100,
            'status' => 'active',
        ]);

        $updateResponse->assertRedirect(route('admin.products.index', $store));
        $created->refresh();
        $this->assertEquals(1100, $created->weight);
        $this->assertEquals('1,1 kg', $created->formattedWeight());
    }

    public function test_validasi_berat_produk_menolak_angka_tidak_valid(): void
    {
        [$admin, $store] = $this->setupStoreAndProducts();

        $response = $this->actingAs($admin)->post(route('admin.products.store', $store), [
            'name' => 'Barang Invalid',
            'selling_price' => 50000,
            'weight' => -100, // Negatif tidak valid
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['weight']);
    }

    public function test_shipping_rates_api_menggunakan_berat_riil_produk_dan_bundle(): void
    {
        [$admin, $store, $kaos, $celana] = $this->setupStoreAndProducts();

        // 1 Paket Casual Set: 1 Kaos (200 gr) + 1 Celana (450 gr) = 650 gr
        $bundleCasual = Product::create([
            'store_id' => $store->id,
            'name' => 'Paket Casual Set',
            'slug' => 'paket-casual-set',
            'selling_price' => 210000,
            'is_bundle' => true,
            'status' => 'active',
        ]);

        $bundleCasual->bundleItems()->attach([
            $kaos->id => ['quantity' => 1],
            $celana->id => ['quantity' => 1],
        ]);

        // Mock BiteshipService untuk memverifikasi payload weight yang dikirimkan
        $mockBiteship = Mockery::mock(BiteshipService::class);
        $mockBiteship->shouldReceive('isConfigured')->andReturn(true);

        // Jika membeli 2 set bundleCasual (650 gr x 2 = 1.300 gr)
        $mockBiteship->shouldReceive('checkRates')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                Mockery::on(function ($items) {
                    // Verifikasi bahwa weight yang dihitung adalah 1.300 gram (650g * 2)
                    return count($items) === 1
                        && $items[0]['weight'] === 1300
                        && $items[0]['quantity'] === 2;
                }),
                Mockery::any()
            )
            ->andReturn([
                'success' => true,
                'rates' => [
                    [
                        'courier_code' => 'jne',
                        'courier_name' => 'JNE',
                        'service_code' => 'reg',
                        'service_name' => 'Reguler',
                        'price' => 24000,
                        'etd' => '1-2 hari',
                    ],
                ],
            ]);

        $this->app->instance(BiteshipService::class, $mockBiteship);

        $response = $this->postJson(route('store.shipping.rates', $store->slug), [
            'postal_code' => '10110',
            'product_id' => $bundleCasual->id,
            'quantity' => 2,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'rates' => [
                [
                    'courier_code' => 'jne',
                    'price' => 24000,
                ],
            ],
        ]);
    }

    public function test_halaman_produk_dan_checkout_menampilkan_informasi_berat(): void
    {
        [$admin, $store, $kaos, $celana] = $this->setupStoreAndProducts();

        $bundle = Product::create([
            'store_id' => $store->id,
            'name' => 'Paket Setelan Harian',
            'slug' => 'paket-setelan-harian',
            'selling_price' => 220000,
            'is_bundle' => true,
            'status' => 'active',
        ]);

        $bundle->bundleItems()->attach([
            $kaos->id => ['quantity' => 1],
            $celana->id => ['quantity' => 1],
        ]);

        // Halaman Detail Produk menampilkan berat paket
        $pdpResponse = $this->get(route('store.product', [$store->slug, $bundle->slug]));
        $pdpResponse->assertOk();
        $pdpResponse->assertSee('Berat:');
        $pdpResponse->assertSee('650 gr');

        // Halaman Checkout Direct Buy menampilkan total berat pengiriman
        $checkoutResponse = $this->get(route('store.checkout', [
            'storeSlug' => $store->slug,
            'product_id' => $bundle->id,
            'quantity' => 2,
        ]));

        $checkoutResponse->assertOk();
        $checkoutResponse->assertSee('Total Berat Kirim');
        $checkoutResponse->assertSee('1,3 kg');
    }
}

