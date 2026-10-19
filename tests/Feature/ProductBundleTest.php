<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\HppService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductBundleTest extends TestCase
{
    use RefreshDatabase;

    protected function setupStoreAndProducts(): array
    {
        $admin = User::create(['name' => 'Admin Toko', 'email' => 'admin@toko.com', 'role' => 'admin', 'is_active' => true]);
        $store = Store::create(['name' => 'Toko Fashion', 'slug' => 'toko-fashion', 'status' => 'active']);
        $store->users()->attach($admin->id, ['is_primary' => true]);

        $atasan = Product::create([
            'store_id' => $store->id,
            'name' => 'Kemeja Linen Putih',
            'slug' => 'kemeja-linen-putih',
            'sku' => 'ATASAN-01',
            'selling_price' => 120000,
            'stock' => 10,
            'status' => 'active',
        ]);

        $bawahan = Product::create([
            'store_id' => $store->id,
            'name' => 'Celana Kulot Khaki',
            'slug' => 'celana-kulot-khaki',
            'sku' => 'BAWAHAN-01',
            'selling_price' => 150000,
            'stock' => 6,
            'status' => 'active',
        ]);

        return [$admin, $store, $atasan, $bawahan];
    }

    public function test_stok_bundle_dihitung_otomatis_dari_komponen(): void
    {
        [$admin, $store, $atasan, $bawahan] = $this->setupStoreAndProducts();

        // Atasan stock: 10, butuh 1x
        // Bawahan stock: 6, butuh 2x per paket -> max bundle = 6 / 2 = 3
        $bundle = Product::create([
            'store_id' => $store->id,
            'name' => 'One Set Linen Casual',
            'slug' => 'one-set-linen-casual',
            'selling_price' => 380000,
            'is_bundle' => true,
            'status' => 'active',
        ]);

        $bundle->bundleItems()->attach([
            $atasan->id => ['quantity' => 1],
            $bawahan->id => ['quantity' => 2],
        ]);
        $bundle->syncBundleStock();

        $this->assertEquals(3, $bundle->stock);
        $this->assertEquals(3, $bundle->calculateBundleStock());
        $this->assertEquals(3, $bundle->fresh()->stock);

        // Jika stok bawahan bertambah via inventory adjustment menjadi 12 (tambah 6)
        app(InventoryService::class)->adjustStock($bawahan, 6, $admin, 'Restock bawahan');
        // Sekarang bawahan = 12. Bundle = min(10/1, 12/2) = min(10, 6) = 6
        $this->assertEquals(6, $bundle->fresh()->stock);

        // Jika salah satu produk nonaktif, stok bundle langsung 0
        $atasan->update(['status' => 'inactive']);
        $bundle->syncBundleStock();
        $this->assertEquals(0, $bundle->fresh()->stock);
    }

    public function test_validasi_pembuatan_bundle_via_form(): void
    {
        [$admin, $store, $atasan, $bawahan] = $this->setupStoreAndProducts();

        $this->actingAs($admin);

        // 1. Kurang dari 2 produk ditolak
        $response = $this->post(route('admin.products.store', $store), [
            'name' => 'Bundle Gagal 1',
            'selling_price' => 100000,
            'status' => 'active',
            'is_bundle' => 1,
            'bundle_items' => [
                ['product_id' => $atasan->id, 'quantity' => 1],
            ],
        ]);
        $response->assertSessionHasErrors('bundle_items');

        // 2. Produk duplikat dalam satu bundle ditolak
        $response = $this->post(route('admin.products.store', $store), [
            'name' => 'Bundle Duplikat',
            'selling_price' => 100000,
            'status' => 'active',
            'is_bundle' => 1,
            'bundle_items' => [
                ['product_id' => $atasan->id, 'quantity' => 1],
                ['product_id' => $atasan->id, 'quantity' => 2],
            ],
        ]);
        $response->assertSessionHasErrors('bundle_items');

        // 3. Produk dari toko lain ditolak
        $otherStore = Store::create(['name' => 'Toko Sebelah', 'slug' => 'toko-sebelah', 'status' => 'active']);
        $otherProduct = Product::create([
            'store_id' => $otherStore->id,
            'name' => 'Barang Toko Lain',
            'slug' => 'barang-toko-lain',
            'selling_price' => 50000,
            'status' => 'active',
        ]);

        $response = $this->post(route('admin.products.store', $store), [
            'name' => 'Bundle Toko Lain',
            'selling_price' => 100000,
            'status' => 'active',
            'is_bundle' => 1,
            'bundle_items' => [
                ['product_id' => $atasan->id, 'quantity' => 1],
                ['product_id' => $otherProduct->id, 'quantity' => 1],
            ],
        ]);
        $response->assertSessionHasErrors('bundle_items');

        // 4. Sukses jika valid (2 produk, toko sama, bukan bundle)
        $response = $this->post(route('admin.products.store', $store), [
            'name' => 'One Set Keren',
            'selling_price' => 250000,
            'status' => 'active',
            'is_bundle' => 1,
            'bundle_items' => [
                ['product_id' => $atasan->id, 'quantity' => 1],
                ['product_id' => $bawahan->id, 'quantity' => 1],
            ],
        ]);
        $response->assertRedirect(route('admin.products.index', $store));
        $this->assertDatabaseHas('products', ['name' => 'One Set Keren', 'is_bundle' => 1]);
    }

    public function test_bundle_tidak_boleh_bersarang_nested_bundle(): void
    {
        [$admin, $store, $atasan, $bawahan] = $this->setupStoreAndProducts();

        $bundle1 = Product::create([
            'store_id' => $store->id,
            'name' => 'Bundle A',
            'slug' => 'bundle-a',
            'selling_price' => 200000,
            'is_bundle' => true,
            'status' => 'active',
        ]);
        $bundle1->bundleItems()->attach([
            $atasan->id => ['quantity' => 1],
            $bawahan->id => ['quantity' => 1],
        ]);

        $this->actingAs($admin);

        // Mencoba membuat bundle baru yang memuat bundle1
        $response = $this->post(route('admin.products.store', $store), [
            'name' => 'Super Bundle',
            'selling_price' => 300000,
            'status' => 'active',
            'is_bundle' => 1,
            'bundle_items' => [
                ['product_id' => $bundle1->id, 'quantity' => 1],
                ['product_id' => $atasan->id, 'quantity' => 1],
            ],
        ]);
        $response->assertSessionHasErrors('bundle_items');
    }

    public function test_batch_dan_adjustment_ditolak_pada_bundle(): void
    {
        [$admin, $store, $atasan, $bawahan] = $this->setupStoreAndProducts();

        $bundle = Product::create([
            'store_id' => $store->id,
            'name' => 'One Set',
            'slug' => 'one-set',
            'selling_price' => 250000,
            'is_bundle' => true,
            'status' => 'active',
        ]);
        $bundle->bundleItems()->attach([
            $atasan->id => ['quantity' => 1],
            $bawahan->id => ['quantity' => 1],
        ]);

        $inventoryService = app(InventoryService::class);

        // 1. Batch pembelian langsung untuk bundle harus ditolak
        $this->expectException(ValidationException::class);
        $inventoryService->createBatch([
            'store_id' => $store->id,
            'batch_code' => 'BATCH-BUNDLE',
            'purchase_date' => now()->toDateString(),
        ], [
            ['product_id' => $bundle->id, 'quantity' => 10, 'unit_purchase_price' => 200000],
        ], $admin);
    }

    public function test_koreksi_stok_manual_ditolak_pada_bundle(): void
    {
        [$admin, $store, $atasan, $bawahan] = $this->setupStoreAndProducts();

        $bundle = Product::create([
            'store_id' => $store->id,
            'name' => 'One Set',
            'slug' => 'one-set',
            'selling_price' => 250000,
            'is_bundle' => true,
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);
        app(InventoryService::class)->adjustStock($bundle, 5, $admin, 'Koreksi manual bundle');
    }

    public function test_hapus_produk_komponen_dalam_bundle_ditolak(): void
    {
        [$admin, $store, $atasan, $bawahan] = $this->setupStoreAndProducts();

        $bundle = Product::create([
            'store_id' => $store->id,
            'name' => 'One Set Fashion',
            'slug' => 'one-set-fashion',
            'selling_price' => 250000,
            'is_bundle' => true,
            'status' => 'active',
        ]);
        $bundle->bundleItems()->attach([
            $atasan->id => ['quantity' => 1],
            $bawahan->id => ['quantity' => 1],
        ]);

        $this->actingAs($admin);

        // Hapus atasan yang menjadi komponen dalam bundle aktif harus ditolak
        $response = $this->delete(route('admin.products.destroy', [$store, $atasan]));
        $response->assertSessionHasErrors('product');
        $this->assertDatabaseHas('products', ['id' => $atasan->id]);
    }

    public function test_hpp_bundle_adalah_akumulasi_hpp_komponen(): void
    {
        [$admin, $store, $atasan, $bawahan] = $this->setupStoreAndProducts();

        // Atasan batch: beli 10 @ 60.000 -> HPP atasan = 60.000
        app(InventoryService::class)->createBatch([
            'store_id' => $store->id,
            'batch_code' => 'BAT-01',
            'purchase_date' => now()->toDateString(),
        ], [
            ['product_id' => $atasan->id, 'quantity' => 10, 'unit_purchase_price' => 60000],
        ], $admin);

        // Bawahan batch: beli 5 @ 80.000 -> HPP bawahan = 80.000
        app(InventoryService::class)->createBatch([
            'store_id' => $store->id,
            'batch_code' => 'BAT-02',
            'purchase_date' => now()->toDateString(),
        ], [
            ['product_id' => $bawahan->id, 'quantity' => 5, 'unit_purchase_price' => 80000],
        ], $admin);

        $bundle = Product::create([
            'store_id' => $store->id,
            'name' => 'One Set Lengkap',
            'slug' => 'one-set-lengkap',
            'selling_price' => 200000,
            'is_bundle' => true,
            'status' => 'active',
        ]);
        // 1x Atasan (60.000) + 1x Bawahan (80.000) = 140.000
        $bundle->bundleItems()->attach([
            $atasan->id => ['quantity' => 1],
            $bawahan->id => ['quantity' => 1],
        ]);

        $hppService = app(HppService::class);
        $this->assertEquals(140000, $hppService->hppForProduct($bundle));
        $this->assertEquals(60000, $hppService->marginForProduct($bundle)); // 200.000 - 140.000
    }

    public function test_halaman_produk_publik_menampilkan_komponen_dan_penghematan(): void
    {
        [$admin, $store, $atasan, $bawahan] = $this->setupStoreAndProducts();

        // Total eceran: 120.000 + 150.000 = 270.000
        // Harga paket: 250.000 -> hemat 20.000
        $bundle = Product::create([
            'store_id' => $store->id,
            'name' => 'One Set Hemat',
            'slug' => 'one-set-hemat',
            'selling_price' => 250000,
            'is_bundle' => true,
            'status' => 'active',
        ]);
        $bundle->bundleItems()->attach([
            $atasan->id => ['quantity' => 1],
            $bawahan->id => ['quantity' => 1],
        ]);
        $bundle->syncBundleStock();

        // Akses halaman bundle publik
        $response = $this->get(route('store.product', [$store->slug, $bundle->slug]));
        $response->assertOk();
        $response->assertSee('One Set Hemat');
        $response->assertSee('Komponen Paket');
        $response->assertSee('Kemeja Linen Putih');
        $response->assertSee('Celana Kulot Khaki');
        $response->assertSee('Hemat Rp 20.000');

        // Akses halaman produk satuan atasan (cross-sell bundle)
        $responseAtasan = $this->get(route('store.product', [$store->slug, $atasan->slug]));
        $responseAtasan->assertOk();
        $responseAtasan->assertSee('Tersedia dalam Paket Setelan Hemat');
        $responseAtasan->assertSee('One Set Hemat');
    }

    public function test_produk_tunggal_dapat_disembunyikan_dari_katalog_tetap_menyuplai_stok_bundle(): void
    {
        [$admin, $store, $atasan, $bawahan] = $this->setupStoreAndProducts();

        // Sembunyikan produk satuan atasan dari katalog publik
        $atasan->update(['is_hidden_from_catalog' => true]);

        $bundle = Product::create([
            'store_id' => $store->id,
            'name' => 'Setelan Eksklusif',
            'slug' => 'setelan-eksklusif',
            'selling_price' => 250000,
            'is_bundle' => true,
            'status' => 'active',
        ]);
        $bundle->bundleItems()->attach([
            $atasan->id => ['quantity' => 1],
            $bawahan->id => ['quantity' => 1],
        ]);
        $bundle->syncBundleStock();

        // 1. Atasan tidak boleh muncul di katalog toko
        $responseCatalog = $this->get($store->url());
        $responseCatalog->assertOk();
        $responseCatalog->assertDontSee($atasan->name);
        $responseCatalog->assertSee($bawahan->name);
        $responseCatalog->assertSee('Setelan Eksklusif');

        // 2. Stok bundle tetap normal (min(10, 6) = 6)
        $this->assertEquals(6, $bundle->fresh()->stock);

        // 3. Akses langsung PDP atasan menampilkan catatan khusus
        $responseDirect = $this->get(route('store.product', [$store->slug, $atasan->slug]));
        $responseDirect->assertOk();
        $responseDirect->assertSee('Produk satuan ini disembunyikan dari katalog umum');

        // 4. Admin dapat memfilter produk tersembunyi
        $this->actingAs($admin);
        $responseAdminHidden = $this->get(route('admin.products.index', [$store, 'visibility' => 'hidden']));
        $responseAdminHidden->assertOk();
        $responseAdminHidden->assertSee($atasan->name);
        $responseAdminHidden->assertDontSee($bawahan->name);

        $responseAdminVisible = $this->get(route('admin.products.index', [$store, 'visibility' => 'visible']));
        $responseAdminVisible->assertOk();
        $responseAdminVisible->assertDontSee($atasan->name);
        $responseAdminVisible->assertSee($bawahan->name);
    }

    public function test_pengaturan_toko_otomatis_sembunyikan_komponen_bundle(): void
    {
        [$admin, $store, $atasan, $bawahan] = $this->setupStoreAndProducts();

        $standalone = Product::create([
            'store_id' => $store->id,
            'name' => 'Jaket Jeans Biasa',
            'slug' => 'jaket-jeans-biasa',
            'selling_price' => 200000,
            'stock' => 5,
            'status' => 'active',
        ]);

        $bundle = Product::create([
            'store_id' => $store->id,
            'name' => 'One Set Toko',
            'slug' => 'one-set-toko',
            'selling_price' => 250000,
            'is_bundle' => true,
            'status' => 'active',
        ]);
        $bundle->bundleItems()->attach([
            $atasan->id => ['quantity' => 1],
            $bawahan->id => ['quantity' => 1],
        ]);

        // Aktifkan pengaturan toko: hide_component_products = true
        $store->update(['hide_component_products' => true]);

        $response = $this->get($store->url());
        $response->assertOk();
        // Atasan & Bawahan otomatis disembunyikan karena sudah menjadi komponen setelan
        $response->assertDontSee($atasan->name);
        $response->assertDontSee($bawahan->name);
        // Jaket & Bundle tetap tampil
        $response->assertSee('Jaket Jeans Biasa');
        $response->assertSee('One Set Toko');
    }
}

