<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseBatch;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeStoreWithAdmin(string $slug = 'toko-list'): array
    {
        $admin = User::create(['name' => 'Admin List', 'email' => "admin-{$slug}@toko.com", 'role' => 'admin', 'is_active' => true]);
        $store = Store::create(['name' => 'Toko '.$slug, 'slug' => $slug, 'status' => 'active']);
        $store->users()->attach($admin->id, ['is_primary' => true]);

        return [$admin, $store];
    }

    protected function makeProduct(Store $store, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'store_id' => $store->id,
            'name' => 'Kaos Polos',
            'slug' => 'kaos-polos',
            'selling_price' => 50000,
            'stock' => 5,
            'status' => 'active',
        ], $overrides));
    }

    public function test_guest_ditolak_di_api_daftar_kategori(): void
    {
        [, $store] = $this->makeStoreWithAdmin();

        $this->getJson(route('api.categories.index', $store))->assertUnauthorized();
    }

    public function test_admin_mendapat_daftar_kategori_dengan_filter_status(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        Category::create(['store_id' => $store->id, 'name' => 'Kaos', 'slug' => 'kaos', 'is_active' => true]);
        Category::create(['store_id' => $store->id, 'name' => 'Jaket', 'slug' => 'jaket', 'is_active' => false]);

        $response = $this->actingAs($admin)->getJson(route('api.categories.index', $store));
        $response->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug', 'children_count', 'products_count', 'sort_order', 'is_active', 'edit_url', 'destroy_url']],
                'meta' => ['current_page', 'last_page', 'total', 'from', 'to'],
            ]);

        $filter = $this->actingAs($admin)->getJson(route('api.categories.index', [$store, 'status' => 'inactive']));
        $filter->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'Jaket');
    }

    public function test_admin_mendapat_struktur_pohon_kategori_hirarki(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $root = Category::create(['store_id' => $store->id, 'name' => 'Fashion', 'slug' => 'fashion', 'is_active' => true]);
        $sub = Category::create(['store_id' => $store->id, 'name' => 'Pakaian Pria', 'slug' => 'pakaian-pria', 'parent_id' => $root->id, 'is_active' => true]);
        $subSub = Category::create(['store_id' => $store->id, 'name' => 'Kaos Pria', 'slug' => 'kaos-pria', 'parent_id' => $sub->id, 'is_active' => true]);

        $response = $this->actingAs($admin)->getJson(route('api.categories.tree', $store));
        $response->assertOk()
            ->assertJsonPath('meta.total_roots', 1)
            ->assertJsonPath('meta.total_categories', 3)
            ->assertJsonPath('data.0.id', $root->id)
            ->assertJsonPath('data.0.children.0.id', $sub->id)
            ->assertJsonPath('data.0.children.0.children.0.id', $subSub->id);
    }

    public function test_admin_toko_lain_ditolak_di_api_kategori(): void
    {
        [$admin] = $this->makeStoreWithAdmin();

        $tokoLain = Store::create(['name' => 'Toko Lain', 'slug' => 'toko-lain-api', 'status' => 'active']);

        $this->actingAs($admin)->getJson(route('api.categories.index', $tokoLain))->assertForbidden();
    }

    public function test_hapus_kategori_terpakai_ditolak_dan_kategori_kosong_berhasil(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $terpakai = Category::create(['store_id' => $store->id, 'name' => 'Kaos', 'slug' => 'kaos', 'is_active' => true]);
        $this->makeProduct($store, ['category_id' => $terpakai->id]);

        $this->actingAs($admin)
            ->deleteJson(route('api.categories.destroy', [$store, $terpakai]))
            ->assertStatus(422);

        $this->assertDatabaseHas('categories', ['id' => $terpakai->id, 'deleted_at' => null]);

        $kosong = Category::create(['store_id' => $store->id, 'name' => 'Aksesoris', 'slug' => 'aksesoris', 'is_active' => true]);

        $this->actingAs($admin)
            ->deleteJson(route('api.categories.destroy', [$store, $kosong]))
            ->assertOk()
            ->assertJsonPath('action', 'deleted');

        $this->assertSoftDeleted('categories', ['id' => $kosong->id]);
    }

    public function test_admin_mendapat_daftar_batch_dengan_filter_pencarian(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        PurchaseBatch::create(['store_id' => $store->id, 'batch_code' => 'BATCH-ALPHA', 'purchase_date' => now()->toDateString(), 'supplier_name' => 'PT Alpha', 'status' => 'active', 'created_by' => $admin->id]);
        PurchaseBatch::create(['store_id' => $store->id, 'batch_code' => 'BATCH-BETA', 'purchase_date' => now()->toDateString(), 'supplier_name' => 'PT Beta', 'status' => 'active', 'created_by' => $admin->id]);

        $this->actingAs($admin)
            ->getJson(route('api.batches.index', [$store, 'q' => 'alpha']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.batch_code', 'BATCH-ALPHA');

        $this->actingAs($admin)->getJson(route('api.batches.index', $store))
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data' => [['batch_code', 'purchase_date_formatted', 'supplier_name', 'items_count', 'status', 'show_url']]]);
    }

    public function test_admin_mendapat_daftar_stok_dengan_filter_low_dan_empty(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        $this->makeProduct($store, ['name' => 'Habis', 'slug' => 'habis', 'stock' => 0]);
        $this->makeProduct($store, ['name' => 'Rendah', 'slug' => 'rendah', 'stock' => 3]);
        $this->makeProduct($store, ['name' => 'Aman', 'slug' => 'aman', 'stock' => 20]);

        $low = $this->actingAs($admin)->getJson(route('api.stock.index', [$store, 'filter' => 'low']));
        $low->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'Rendah');

        $empty = $this->actingAs($admin)->getJson(route('api.stock.index', [$store, 'filter' => 'empty']));
        $empty->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'Habis');

        $this->actingAs($admin)->getJson(route('api.stock.index', $store))
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [['id', 'name', 'category', 'stock', 'is_bundle', 'adjust_url']]]);
    }

    public function test_riwayat_stok_dengan_filter_tipe(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();
        $product = $this->makeProduct($store);

        app(InventoryService::class)->adjustStock($product, -2, $admin, 'koreksi opname riwayat');

        $response = $this->actingAs($admin)->getJson(route('api.stock.history', $store));
        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data' => [['created_at_formatted', 'product_name', 'movement_type', 'quantity', 'notes', 'creator_name']]]);

        $adjustment = $this->actingAs($admin)->getJson(route('api.stock.history', [$store, 'type' => 'adjustment']));
        $adjustment->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.quantity', -2);

        $keluar = $this->actingAs($admin)->getJson(route('api.stock.history', [$store, 'type' => 'out']));
        $keluar->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_aktivitas_toko_tercatat_di_api(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();

        AuditLogger::log('product.updated', null, 'Uji aktivitas toko', null, $admin, $store->id);

        $this->actingAs($admin)->getJson(route('api.stock.activity', $store))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.action', 'product.updated')
            ->assertJsonPath('data.0.description', 'Uji aktivitas toko');
    }

    public function test_superadmin_mendapat_api_daftar_toko_dan_pengguna(): void
    {
        [, $store] = $this->makeStoreWithAdmin();
        $superadmin = User::create(['name' => 'Super List', 'email' => 'super-list@toko.com', 'role' => 'superadmin', 'is_active' => true]);

        $this->actingAs($superadmin)->getJson(route('api.superadmin.stores.index'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', $store->name)
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'url', 'products_count', 'categories_count', 'purchase_batches_count', 'status', 'show_url']]]);

        $this->actingAs($superadmin)->getJson(route('api.superadmin.users.index'))
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'role', 'is_active', 'stores', 'edit_url']]]);

        $this->actingAs($superadmin)->getJson(route('api.superadmin.users.index', ['role' => 'superadmin']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.role', 'superadmin');
    }

    public function test_superadmin_mendapat_katalog_lintas_toko(): void
    {
        [$admin, $store] = $this->makeStoreWithAdmin();
        $superadmin = User::create(['name' => 'Super Katalog', 'email' => 'super-katalog@toko.com', 'role' => 'superadmin', 'is_active' => true]);

        $category = Category::create(['store_id' => $store->id, 'name' => 'Kaos', 'slug' => 'kaos', 'is_active' => true]);
        $product = $this->makeProduct($store, ['category_id' => $category->id]);

        PurchaseBatch::create(['store_id' => $store->id, 'batch_code' => 'BATCH-KATALOG', 'purchase_date' => now()->toDateString(), 'supplier_name' => 'PT Katalog', 'status' => 'active', 'created_by' => $admin->id]);

        app(InventoryService::class)->adjustStock($product, 2, $admin, 'koreksi katalog lintas');
        AuditLogger::log('product.updated', $product, 'Uji audit lintas toko', null, $admin, $store->id);

        $this->actingAs($superadmin)->getJson(route('api.superadmin.catalog.categories', ['store_id' => $store->id]))
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.store_name', $store->name);

        $this->actingAs($superadmin)->getJson(route('api.superadmin.catalog.products'))
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'Kaos Polos');

        $this->actingAs($superadmin)->getJson(route('api.superadmin.catalog.batches'))
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.batch_code', 'BATCH-KATALOG');

        $this->actingAs($superadmin)->getJson(route('api.superadmin.catalog.stock'))
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.movement_type', 'adjustment');

        $this->actingAs($superadmin)->getJson(route('api.superadmin.catalog.audit', ['action' => 'product']))
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.action', 'product.updated');
    }

    public function test_admin_biasa_ditolak_di_api_superadmin(): void
    {
        [$admin] = $this->makeStoreWithAdmin();

        $this->actingAs($admin)->getJson(route('api.superadmin.stores.index'))->assertForbidden();
        $this->actingAs($admin)->getJson(route('api.superadmin.users.index'))->assertForbidden();
        $this->actingAs($admin)->getJson(route('api.superadmin.catalog.products'))->assertForbidden();
    }
}
