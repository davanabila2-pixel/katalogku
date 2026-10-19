<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeStoreWithAdmin(): array
    {
        $admin = User::create(['name' => 'Admin API', 'email' => 'admin-api@toko.com', 'role' => 'admin', 'is_active' => true]);
        $store = Store::create(['name' => 'Toko API', 'slug' => 'toko-api', 'status' => 'active']);
        $store->users()->attach($admin->id, ['is_primary' => true]);

        return [$admin, $store];
    }

    public function test_guest_tidak_bisa_mengakses_api_produk(): void
    {
        [, $store] = $this->makeStoreWithAdmin();

        $this->getJson(route('api.products.index', $store))->assertUnauthorized();
    }

    public function test_admin_mendapat_daftar_produk_json_dengan_filter(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $kategori = Category::create([
            'store_id' => $store->id,
            'name' => 'Kaos',
            'slug' => 'kaos',
            'is_active' => true,
        ]);

        Product::create([
            'store_id' => $store->id,
            'category_id' => $kategori->id,
            'name' => 'Kaos Polos Hitam',
            'slug' => 'kaos-polos-hitam',
            'sku' => 'KAOS-01',
            'selling_price' => 50000,
            'stock' => 3,
            'status' => 'active',
        ]);

        Product::create([
            'store_id' => $store->id,
            'name' => 'Jaket Hoodie',
            'slug' => 'jaket-hoodie',
            'sku' => 'JAKET-01',
            'selling_price' => 220000,
            'stock' => 0,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->getJson(route('api.products.index', $store));
        $response->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'sku', 'selling_price', 'selling_price_formatted', 'stock', 'status', 'is_bundle', 'is_hidden', 'edit_url']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'],
            ]);

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Kaos Polos Hitam'));
        $this->assertTrue($names->contains('Jaket Hoodie'));

        $harga = collect($response->json('data'))->firstWhere('name', 'Kaos Polos Hitam');
        $this->assertSame('Rp 50.000', $harga['selling_price_formatted']);

        // Filter pencarian (nama/SKU)
        $filterQ = $this->actingAs($admin)->getJson(route('api.products.index', [$store, 'q' => 'kaos']));
        $filterQ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'Kaos Polos Hitam');

        // Filter kategori
        $filterKategori = $this->actingAs($admin)->getJson(route('api.products.index', [$store, 'category_id' => $kategori->id]));
        $filterKategori->assertOk()->assertJsonPath('meta.total', 1);

        // Filter status
        $filterStatus = $this->actingAs($admin)->getJson(route('api.products.index', [$store, 'status' => 'draft']));
        $filterStatus->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'Jaket Hoodie');

        // Filter stok kosong
        $filterKosong = $this->actingAs($admin)->getJson(route('api.products.index', [$store, 'stock' => 'empty']));
        $filterKosong->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'Jaket Hoodie');
    }

    public function test_admin_toko_lain_tidak_bisa_mengakses_api_produk(): void
    {
        [$admin] = $this->makeStoreWithAdmin();

        $tokoLain = Store::create(['name' => 'Toko Lain', 'slug' => 'toko-lain', 'status' => 'active']);

        $this->actingAs($admin)->getJson(route('api.products.index', $tokoLain))->assertForbidden();
    }

    public function test_superadmin_bisa_mengakses_api_produk_semua_toko(): void
    {
        [, $store] = $this->makeStoreWithAdmin();

        $superadmin = User::create(['name' => 'Super', 'email' => 'super@toko.com', 'role' => 'superadmin', 'is_active' => true]);

        $this->actingAs($superadmin)->getJson(route('api.products.index', $store))->assertOk();
    }

    public function test_halaman_produk_menyiapkan_skeleton_dan_url_api(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $response = $this->actingAs($admin)->get(route('admin.products.index', $store));

        $response->assertOk()
            ->assertSee('Memuat data produk', false)
            ->assertSee('id="product-rows"', false)
            ->assertSee(route('api.products.index', $store), false);
    }
}
