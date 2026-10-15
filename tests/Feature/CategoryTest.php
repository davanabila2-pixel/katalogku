<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setupStore(?User $admin = null): Store
    {
        $admin ??= User::create(['name' => 'Admin', 'email' => 'admin@x.com', 'role' => 'admin', 'is_active' => true]);
        $store = Store::create(['name' => 'Toko Uji', 'status' => 'active']);
        $store->users()->attach($admin->id, ['is_primary' => true]);

        return $store;
    }

    public function test_admin_dapat_crud_kategori(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin', 'is_active' => true]);
        $store = $this->setupStore($admin);

        $this->actingAs($admin)->post("/admin/{$store->id}/categories", [
            'name' => 'Atasan', 'sort_order' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', ['store_id' => $store->id, 'name' => 'Atasan', 'slug' => 'atasan']);
    }

    public function test_subkategori_mengikuti_induk_yang_benar(): void
    {
        $store = $this->setupStore();
        $parent = Category::create(['store_id' => $store->id, 'name' => 'Atasan', 'slug' => 'atasan']);
        $child = Category::create(['store_id' => $store->id, 'parent_id' => $parent->id, 'name' => 'Kaos', 'slug' => 'kaos']);

        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertContains($child->id, $parent->descendantIds());
        $this->get("/{$store->slug}/kategori/atasan/kaos")->assertOk()->assertSee('Kaos');
    }

    public function test_kategori_tidak_boleh_menjadi_induk_diri_sendiri(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin', 'is_active' => true]);
        $store = $this->setupStore($admin);
        $cat = Category::create(['store_id' => $store->id, 'name' => 'Atasan', 'slug' => 'atasan']);

        $this->actingAs($admin)->put("/admin/{$store->id}/categories/{$cat->id}", [
            'name' => 'Atasan', 'parent_id' => $cat->id,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_relasi_kategori_tidak_boleh_siklus(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin', 'is_active' => true]);
        $store = $this->setupStore($admin);
        $parent = Category::create(['store_id' => $store->id, 'name' => 'Atasan', 'slug' => 'atasan']);
        $child = Category::create(['store_id' => $store->id, 'parent_id' => $parent->id, 'name' => 'Kaos', 'slug' => 'kaos']);

        $this->actingAs($admin)->put("/admin/{$store->id}/categories/{$parent->id}", [
            'name' => 'Atasan', 'parent_id' => $child->id,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_produk_tidak_dapat_memakai_kategori_toko_lain(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin', 'is_active' => true]);
        $storeA = $this->setupStore($admin);
        $storeB = Store::create(['name' => 'Toko B', 'status' => 'active']);
        $foreign = Category::create(['store_id' => $storeB->id, 'name' => 'Luar', 'slug' => 'luar']);

        $this->actingAs($admin)->post("/admin/{$storeA->id}/products", [
            'name' => 'Kaos', 'selling_price' => 50000, 'status' => 'draft', 'category_id' => $foreign->id,
        ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('products', ['store_id' => $storeA->id, 'name' => 'Kaos']);
    }

    public function test_kategori_nonaktif_tidak_tampil_di_katalog(): void
    {
        $store = $this->setupStore();
        Category::create(['store_id' => $store->id, 'name' => 'Sembunyi', 'slug' => 'sembunyi', 'is_active' => false]);

        $this->get("/{$store->slug}")->assertOk()->assertDontSee('Sembunyi');
        $this->get("/{$store->slug}/kategori/sembunyi")->assertNotFound();
    }

    public function test_hapus_kategori_berisi_produk_ditolak(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin', 'is_active' => true]);
        $store = $this->setupStore($admin);
        $cat = Category::create(['store_id' => $store->id, 'name' => 'Atasan', 'slug' => 'atasan']);
        Product::create(['store_id' => $store->id, 'category_id' => $cat->id, 'name' => 'Kaos', 'slug' => 'kaos', 'selling_price' => 10000, 'status' => 'active']);

        $this->actingAs($admin)->delete("/admin/{$store->id}/categories/{$cat->id}")
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('products', ['slug' => 'kaos']);
    }

    public function test_filter_kategori_bekerja_dan_produk_nonaktif_disembunyikan(): void
    {
        $store = $this->setupStore();
        $cat = Category::create(['store_id' => $store->id, 'name' => 'Atasan', 'slug' => 'atasan']);
        Product::create(['store_id' => $store->id, 'category_id' => $cat->id, 'name' => 'Tampil', 'slug' => 'tampil', 'selling_price' => 10000, 'status' => 'active']);
        Product::create(['store_id' => $store->id, 'category_id' => $cat->id, 'name' => 'Draft', 'slug' => 'draft', 'selling_price' => 10000, 'status' => 'draft']);

        $this->get("/{$store->slug}/kategori/atasan")->assertOk()->assertSee('Tampil')->assertDontSee('Draft');
    }
}
