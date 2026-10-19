<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tes endpoint API untuk halaman form admin (kategori, produk, profil,
 * batch): daftar pilihan form-data + simpan/perbarui tanpa reload.
 */
class AdminFormApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeStoreWithAdmin(string $slug = 'toko-form'): array
    {
        $admin = User::create(['name' => 'Admin Form', 'email' => "admin-{$slug}@toko.com", 'role' => 'admin', 'is_active' => true]);
        $store = Store::create(['name' => 'Toko '.$slug, 'slug' => $slug, 'status' => 'active']);
        $store->users()->attach($admin->id, ['is_primary' => true]);

        return [$admin, $store];
    }

    protected function makeProduct(Store $store, array $overrides = []): Product
    {
        static $counter = 0;
        $counter++;

        return Product::create(array_merge([
            'store_id' => $store->id,
            'name' => 'Kaos Polos '.$counter,
            'slug' => 'kaos-polos-'.$counter,
            'selling_price' => 50000,
            'stock' => 5,
            'status' => 'active',
        ], $overrides));
    }

    public function test_pilihan_kategori_meniru_perilaku_form_web(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $root = Category::create(['store_id' => $store->id, 'name' => 'Kaos', 'slug' => 'kaos', 'is_active' => true]);
        $child = Category::create(['store_id' => $store->id, 'name' => 'Kaos Panjang', 'slug' => 'kaos-panjang', 'parent_id' => $root->id, 'is_active' => true]);

        // Saat menambah: hanya kategori utama (roots).
        $this->actingAs($admin)->getJson(route('api.categories.options', $store))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $root->id);

        // Saat mengedit: semua kategori kecuali dirinya sendiri.
        $this->actingAs($admin)->getJson(route('api.categories.options', [$store, 'exclude' => $root->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $child->id);
    }

    public function test_kategori_dibuat_lewat_api_dengan_slug_otomatis(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $this->actingAs($admin)->postJson(route('api.categories.store', $store), [
            'name' => 'Aksesoris Rajut',
            'description' => 'Kategori aksesoris',
            'sort_order' => 3,
        ])->assertCreated()->assertJsonPath('message', 'Kategori dibuat.');

        $this->assertDatabaseHas('categories', [
            'store_id' => $store->id,
            'name' => 'Aksesoris Rajut',
            'slug' => 'aksesoris-rajut',
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }

    public function test_kategori_validasi_gagal_mengembalikan_422(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $this->actingAs($admin)->postJson(route('api.categories.store', $store), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_kategori_diperbarui_lewat_api(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();
        $category = Category::create(['store_id' => $store->id, 'name' => 'Kaos', 'slug' => 'kaos', 'is_active' => true]);

        $this->actingAs($admin)->putJson(route('api.categories.update', [$store, $category]), [
            'name' => 'Kaos Premium',
            'is_active' => 1,
        ])->assertOk()->assertJsonPath('message', 'Kategori diperbarui.');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Kaos Premium', 'is_active' => true]);
    }

    public function test_form_data_produk_memuat_kategori_aktif_dan_komponen_satuan(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        Category::create(['store_id' => $store->id, 'name' => 'Aktif', 'slug' => 'aktif', 'is_active' => true]);
        Category::create(['store_id' => $store->id, 'name' => 'Nonaktif', 'slug' => 'nonaktif', 'is_active' => false]);
        $this->makeProduct($store);
        $bundle = $this->makeProduct($store, ['name' => 'Setelan Awal', 'slug' => 'setelan-awal', 'is_bundle' => true]);

        $response = $this->actingAs($admin)->getJson(route('api.products.form_data', $store));
        $response->assertOk()
            ->assertJsonCount(1, 'categories')
            ->assertJsonPath('categories.0.name', 'Aktif')
            ->assertJsonStructure([
                'categories' => [['id', 'name']],
                'components' => [['id', 'name', 'sku', 'selling_price', 'stock']],
            ]);

        // Produk setelan tidak boleh muncul sebagai komponen.
        $componentIds = collect($response->json('components'))->pluck('id');
        $this->assertFalse($componentIds->contains($bundle->id));
    }

    public function test_form_data_edit_produk_memuat_paket_dan_statistik(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $atasan = $this->makeProduct($store, ['stock' => 4]);
        $bawahan = $this->makeProduct($store, ['stock' => 7]);
        $bundle = $this->makeProduct($store, ['name' => 'Setelan Kasual', 'slug' => 'setelan-kasual', 'is_bundle' => true]);
        $bundle->bundleItems()->sync([
            $atasan->id => ['quantity' => 1],
            $bawahan->id => ['quantity' => 2],
        ]);
        $bundle->syncBundleStock();

        $response = $this->actingAs($admin)->getJson(route('api.products.product_form_data', [$store, $bundle]));
        $response->assertOk()
            ->assertJsonPath('product.id', $bundle->id)
            ->assertJsonPath('product.is_bundle', true)
            ->assertJsonPath('product.stock', 3) // min(4/1, 7/2) = 3 paket
            ->assertJsonCount(2, 'bundle_items')
            ->assertJsonStructure([
                'product' => ['id', 'name', 'slug', 'sku', 'category_id', 'description', 'selling_price', 'status', 'is_bundle', 'is_hidden_from_catalog', 'stock', 'hpp', 'margin', 'update_url'],
                'bundle_items' => [['product_id', 'quantity']],
                'barcode' => ['code', 'label_url', 'scan_url', 'regenerate_url'],
                'components' => [['id', 'name', 'selling_price', 'stock']],
            ]);

        // Komponen tidak memuat produk itu sendiri.
        $componentIds = collect($response->json('components'))->pluck('id');
        $this->assertFalse($componentIds->contains($bundle->id));
    }

    public function test_produk_dibuat_lewat_api(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $this->actingAs($admin)->postJson(route('api.products.store', $store), [
            'name' => 'Kemeja Flanel',
            'selling_price' => 125000,
            'status' => 'active',
            'sku' => 'KF-01',
        ])->assertCreated()->assertJsonPath('message', 'Produk dibuat.');

        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'name' => 'Kemeja Flanel',
            'slug' => 'kemeja-flanel',
            'status' => 'active',
        ]);
    }

    public function test_bundle_dibuat_lewat_api_menyinkronkan_komponen_dan_stok(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();
        $atasan = $this->makeProduct($store, ['stock' => 6]);
        $bawahan = $this->makeProduct($store, ['stock' => 3]);

        $this->actingAs($admin)->postJson(route('api.products.store', $store), [
            'name' => 'Setelan Pantai',
            'selling_price' => 150000,
            'status' => 'active',
            'is_bundle' => 1,
            'bundle_items' => [
                ['product_id' => $atasan->id, 'quantity' => 1],
                ['product_id' => $bawahan->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $bundle = Product::where('slug', 'setelan-pantai')->first();
        $this->assertNotNull($bundle);
        $this->assertTrue($bundle->isBundle());
        $this->assertSame(2, $bundle->bundleItems()->count());
        $this->assertSame(3, (int) $bundle->fresh()->stock);
    }

    public function test_produk_diperbarui_lewat_api(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();
        $product = $this->makeProduct($store, ['name' => 'Nama Lama', 'slug' => 'nama-lama']);

        $this->actingAs($admin)->putJson(route('api.products.update', [$store, $product]), [
            'name' => 'Nama Baru',
            'slug' => 'nama-baru',
            'selling_price' => 60000,
            'status' => 'draft',
            'is_hidden_from_catalog' => 1,
        ])->assertOk()->assertJsonPath('message', 'Produk diperbarui.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Nama Baru',
            'slug' => 'nama-baru',
            'status' => 'draft',
            'is_hidden_from_catalog' => true,
        ]);
    }

    public function test_profil_toko_diperbarui_lewat_api(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $response = $this->actingAs($admin)->putJson(route('api.profile.update', $store), [
            'name' => 'Toko Baru',
            'slug' => 'toko-baru',
            'contact_information' => '0812-0000-1111',
            'hide_component_products' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('store.name', 'Toko Baru')
            ->assertJsonPath('store.slug', 'toko-baru');

        $this->assertStringContainsString('301', $response->json('message'));

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'name' => 'Toko Baru',
            'slug' => 'toko-baru',
            'hide_component_products' => true,
        ]);
    }

    public function test_slug_cadangan_ditolak_di_api_profil(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $this->actingAs($admin)->putJson(route('api.profile.update', $store), [
            'name' => $store->name,
            'slug' => 'admin',
        ])->assertStatus(422)->assertJsonValidationErrors(['slug']);
    }

    public function test_pilihan_produk_batch_dan_pembuatan_batch_lewat_api(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();
        $product = $this->makeProduct($store, ['stock' => 2]);

        $this->actingAs($admin)->getJson(route('api.batches.options', $store))
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonStructure(['data' => [['id', 'name', 'sku', 'stock']], 'suggested_code', 'today']);

        $response = $this->actingAs($admin)->postJson(route('api.batches.store', $store), [
            'batch_code' => 'BATCH-API-1',
            'purchase_date' => now()->toDateString(),
            'supplier_name' => 'PT Uji',
            'additional_cost' => 5000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_purchase_price' => 20000],
            ],
        ]);

        $response->assertCreated();
        $this->assertStringContainsString('Batch dibuat', $response->json('message'));
        $this->assertStringContainsString('/batches/', $response->json('redirect_url'));

        $this->assertDatabaseHas('purchase_batches', ['store_id' => $store->id, 'batch_code' => 'BATCH-API-1']);
        $this->assertSame(12, (int) $product->fresh()->stock);
    }

    public function test_admin_toko_lain_ditolak_di_api_form(): void
    {
        [$admin] = $this->makeStoreWithAdmin();
        $otherStore = Store::create(['name' => 'Toko Lain', 'slug' => 'toko-lain-form', 'status' => 'active']);

        $this->actingAs($admin)->getJson(route('api.products.form_data', $otherStore))->assertForbidden();
        $this->actingAs($admin)->postJson(route('api.categories.store', $otherStore), ['name' => 'X'])->assertForbidden();
        $this->actingAs($admin)->putJson(route('api.profile.update', $otherStore), ['name' => 'X'])->assertForbidden();
        $this->actingAs($admin)->getJson(route('api.batches.options', $otherStore))->assertForbidden();
    }
}
