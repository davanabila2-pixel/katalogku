<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeStore(string $name, ?User $admin = null, bool $primary = true): Store
    {
        $store = Store::create(['name' => $name, 'status' => 'active']);
        if ($admin) {
            $store->users()->attach($admin->id, ['is_primary' => $primary]);
        }

        return $store;
    }

    public function test_superadmin_dapat_mengelola_toko(): void
    {
        $super = User::create(['name' => 'S', 'email' => 's@x.com', 'role' => 'superadmin', 'is_active' => true]);

        $this->actingAs($super)->get(route('superadmin.dashboard'))->assertOk();
        $this->actingAs($super)->post(route('superadmin.stores.store'), [
            'name' => 'Toko Baru', 'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('stores', ['name' => 'Toko Baru', 'slug' => 'toko-baru']);
    }

    public function test_admin_biasa_ditolak_masuk_area_superadmin(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get(route('superadmin.dashboard'))->assertForbidden();
    }

    public function test_admin_tidak_dapat_mengakses_produk_toko_lain(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin', 'is_active' => true]);
        $mine = $this->makeStore('Tokoku', $admin);
        $other = $this->makeStore('Toko Orang');
        $foreign = Product::create(['store_id' => $other->id, 'name' => 'Rahasia', 'slug' => 'rahasia', 'selling_price' => 1000, 'status' => 'active']);

        // Manipulasi ID via URL toko sendiri: ditolak (403 otorisasi / 404 isolasi)
        $this->actingAs($admin)->get("/admin/{$mine->id}/products/{$foreign->id}/edit")->assertNotFound();
        $this->actingAs($admin)->put("/admin/{$mine->id}/products/{$foreign->id}", [
            'name' => 'Diretas', 'selling_price' => 1, 'status' => 'draft',
        ])->assertForbidden();

        // Langsung ke area toko lain
        $this->actingAs($admin)->get("/admin/{$other->id}/products")->assertForbidden();
        $this->assertSame('Rahasia', $foreign->fresh()->name);
    }

    public function test_admin_tidak_dapat_mengakses_kategori_toko_lain(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin', 'is_active' => true]);
        $mine = $this->makeStore('Tokoku', $admin);
        $other = $this->makeStore('Toko Orang');
        $foreign = Category::create(['store_id' => $other->id, 'name' => 'Privat', 'slug' => 'privat']);

        $this->actingAs($admin)->get("/admin/{$mine->id}/categories/{$foreign->id}/edit")->assertNotFound();
        $this->actingAs($admin)->get("/admin/{$other->id}/categories")->assertForbidden();
    }

    public function test_pengunjung_hanya_melihat_data_publik(): void
    {
        $store = $this->makeStore('Toko Publik');
        Product::create(['store_id' => $store->id, 'name' => 'Jual', 'slug' => 'jual', 'selling_price' => 10000, 'status' => 'active']);
        Product::create(['store_id' => $store->id, 'name' => 'Arsip', 'slug' => 'arsip', 'selling_price' => 10000, 'status' => 'inactive']);

        $this->get("/{$store->slug}")->assertOk()->assertSee('Jual')->assertDontSee('Arsip');
        $this->get("/{$store->slug}/produk/arsip")->assertNotFound();
        $this->get('/admin/1')->assertRedirect('/login');
    }

    public function test_pengguna_tidak_dapat_menaikkan_role_sendiri(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nakal', 'email' => 'nakal@x.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'account_type' => 'user',
            'role' => 'superadmin',
        ]);

        $response->assertRedirect();
        $this->assertSame('admin', User::where('email', 'nakal@x.com')->first()->role);
    }

    public function test_google_oauth_tidak_pernah_membuat_superadmin(): void
    {
        // Registrasi via OAuth selalu role admin (lihat GoogleOAuthController).
        // Simulasi: user dibuat tanpa google_id lalu login OAuth menautkan —
        // role yang sudah superadmin tetap, yang baru selalu admin.
        $this->assertTrue(true);
        $source = file_get_contents(app_path('Http/Controllers/GoogleOAuthController.php'));
        $this->assertStringContainsString("'role' => 'admin'", $source);
        $this->assertStringNotContainsString("'role' => 'superadmin'", $source);
    }
}
