<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\StoreSlugHistory;
use App\Models\User;
use App\Services\SlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_dibuat_dengan_format_benar(): void
    {
        $this->assertSame('toko-1', SlugService::make('Toko 1'));
        $this->assertSame('thrift-premium', SlugService::make('Thrift Premium!'));
        $this->assertSame('fashion-byvii', SlugService::make('Fashion Byvii'));
    }

    public function test_slug_unik_dengan_suffix_otomatis(): void
    {
        Store::create(['name' => 'Toko 1', 'status' => 'active']);
        $second = SlugService::uniqueStoreSlug('Toko 1');

        $this->assertSame('toko-1-2', $second);
    }

    public function test_slug_dicadangkan_untuk_route_sistem(): void
    {
        foreach (['login', 'admin', 'superadmin', 'dashboard', 'api', 'auth', 'toko'] as $reserved) {
            $this->assertTrue(SlugService::isReserved($reserved), "{$reserved} harus dicadangkan");
        }

        $slug = SlugService::uniqueStoreSlug('Login');
        $this->assertNotSame('login', $slug);
    }

    protected function seedTwoStores(): array
    {
        $store1 = Store::create(['name' => 'Toko 1', 'status' => 'active']);
        $store2 = Store::create(['name' => 'Toko 2', 'status' => 'active']);

        Product::create(['store_id' => $store1->id, 'name' => 'Kaos Vintage', 'slug' => 'kaos-vintage', 'selling_price' => 50000, 'status' => 'active']);
        Product::create(['store_id' => $store2->id, 'name' => 'Kaos Vintage', 'slug' => 'kaos-vintage', 'selling_price' => 75000, 'status' => 'active']);

        return [$store1, $store2];
    }

    public function test_url_toko_menampilkan_katalog_masing_masing(): void
    {
        [$s1, $s2] = $this->seedTwoStores();

        $this->get('/'.$s1->slug)->assertOk()->assertSee('Toko 1');
        $this->get('/'.$s2->slug)->assertOk()->assertSee('Toko 2');
    }

    public function test_produk_slug_sama_di_toko_berbeda_tidak_tertukar(): void
    {
        [$s1, $s2] = $this->seedTwoStores();

        $this->get("/{$s1->slug}/produk/kaos-vintage")->assertOk()->assertSee('50.000');
        $this->get("/{$s2->slug}/produk/kaos-vintage")->assertOk()->assertSee('75.000');
    }

    public function test_produk_toko_lain_tidak_bisa_diakses_via_url_salah(): void
    {
        [$s1, $s2] = $this->seedTwoStores();
        Product::create(['store_id' => $s2->id, 'name' => 'Eksklusif', 'slug' => 'eksklusif', 'selling_price' => 10000, 'status' => 'active']);

        $this->get("/{$s1->slug}/produk/eksklusif")->assertNotFound();
    }

    public function test_route_sistem_tidak_bentrok_dengan_slug_toko(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
        $this->get('/toko')->assertOk();
        $this->get('/superadmin')->assertRedirect('/login');
    }

    public function test_toko_nonaktif_tidak_dapat_diakses_publik(): void
    {
        $store = Store::create(['name' => 'Tutup', 'status' => 'inactive']);

        $this->get('/'.$store->slug)->assertNotFound();
    }

    public function test_perubahan_slug_mencatat_histori_dan_redirect_301(): void
    {
        $store = Store::create(['name' => 'Toko Lama', 'status' => 'active']);
        $old = $store->slug;

        $store->update(['slug' => 'toko-baru']);

        $this->assertDatabaseHas('store_slug_histories', ['store_id' => $store->id, 'old_slug' => $old, 'new_slug' => 'toko-baru']);
        $this->get('/'.$old)->assertRedirect('/toko-baru', 301);
        $this->get('/toko-baru')->assertOk();
    }

    public function test_sitemap_memuat_toko_dan_produk(): void
    {
        [$s1] = $this->seedTwoStores();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('/'.$s1->slug, false)
            ->assertSee('/produk/kaos-vintage', false);
    }

    public function test_admin_tidak_dapat_mengubah_slug_toko_lain(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@x.com', 'role' => 'admin', 'is_active' => true]);
        $mine = Store::create(['name' => 'Tokoku', 'status' => 'active']);
        $other = Store::create(['name' => 'Toko Orang', 'status' => 'active']);
        $mine->users()->attach($admin->id, ['is_primary' => true]);

        $this->assertTrue($admin->can('updateSlug', $mine));
        $this->assertFalse($admin->can('updateSlug', $other));

        $this->actingAs($admin)
            ->put("/admin/{$other->id}/profil", ['name' => 'Diretas', 'slug' => 'diretas'])
            ->assertForbidden();
    }
}
