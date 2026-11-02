<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setupStore(?User $admin = null): Store
    {
        $admin ??= User::create(['name' => 'Admin', 'email' => 'admin@x.com', 'role' => 'admin', 'is_active' => true]);
        $store = Store::create(['name' => 'Toko Uji', 'status' => 'active']);
        $store->users()->attach($admin->id, ['is_primary' => true]);

        return $store;
    }

    public function test_admin_dapat_crud_tautan(): void
    {
        $store = $this->setupStore();

        $response = $this->actingAs($store->users()->first())->post(route('admin.links.store', $store), [
            'title' => 'Katalog Produk',
            'url' => 'https://contoh.com/katalog',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.links.index', $store));
        $this->assertDatabaseHas('store_links', [
            'store_id' => $store->id,
            'title' => 'Katalog Produk',
            'url' => 'https://contoh.com/katalog',
            'is_active' => true,
        ]);

        $link = StoreLink::firstOrFail();

        $this->actingAs($store->users()->first())
            ->put(route('admin.links.update', [$store, $link]), [
                'title' => 'WhatsApp Toko',
                'url' => 'https://wa.me/6281234567890',
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.links.index', $store));

        $this->assertDatabaseHas('store_links', [
            'id' => $link->id,
            'title' => 'WhatsApp Toko',
            'is_active' => false,
        ]);

        $this->actingAs($store->users()->first())
            ->delete(route('admin.links.destroy', [$store, $link]))
            ->assertRedirect(route('admin.links.index', $store));

        $this->assertSoftDeleted('store_links', ['id' => $link->id]);
    }

    public function test_halaman_tautan_hanya_menampilkan_tautan_aktif(): void
    {
        $store = $this->setupStore();

        StoreLink::create([
            'store_id' => $store->id,
            'title' => 'Tampil Di Halaman',
            'url' => 'https://contoh.com/aktif',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        StoreLink::create([
            'store_id' => $store->id,
            'title' => 'Tersembunyi Total',
            'url' => 'https://contoh.com/nonaktif',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $this->get(route('store.links', $store->slug))
            ->assertOk()
            ->assertSee('Tampil Di Halaman')
            ->assertDontSee('Tersembunyi Total');
    }

    public function test_halaman_tautan_tidak_menampilkan_appbar_dan_footer_serta_menampilkan_slogan(): void
    {
        $store = $this->setupStore();
        $store->update([
            'slogan' => 'Secondhand, First Choice.',
            'description' => 'Deskripsi panjang toko yang tidak perlu muncul di bio link.',
        ]);

        StoreLink::create([
            'store_id' => $store->id,
            'title' => 'Instagram Resmi',
            'url' => 'https://instagram.com/byvii',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->get(route('store.links', $store->slug));

        $response->assertOk();

        // 1. Appbar dan Footer toko TIDAK ditampilkan
        $response->assertDontSee('topbar-store', false);
        $response->assertDontSee('topbar-inner', false);
        $response->assertDontSee('store-footer-inner', false);
        $response->assertDontSee('default-footer-inner', false);

        // 2. Deskripsi diganti oleh Slogan dengan tipografi lk-slogan
        $response->assertSee('lk-slogan', false);
        $response->assertSee('Secondhand, First Choice.', false);
        $response->assertDontSee('Deskripsi panjang toko yang tidak perlu muncul di bio link.', false);

        // 3. Pintasan katalog toko dan tautan aktif ditampilkan
        $response->assertSee('Katalog Produk & Belanja', false);
        $response->assertSee('Instagram Resmi', false);
    }

    public function test_url_tanpa_skema_http_ditolak(): void
    {
        $store = $this->setupStore();

        $this->actingAs($store->users()->first())
            ->from(route('admin.links.index', $store))
            ->post(route('admin.links.store', $store), [
                'title' => 'Berbahaya',
                'url' => 'javascript:alert(1)',
            ])
            ->assertRedirect(route('admin.links.index', $store))
            ->assertSessionHasErrors('url');

        $this->assertSame(0, StoreLink::count());
    }

    public function test_admin_toko_lain_tidak_bisa_mengubah_tautan(): void
    {
        $owner = $this->setupStore();
        $link = StoreLink::create([
            'store_id' => $owner->id,
            'title' => 'Tautan Pemilik',
            'url' => 'https://contoh.com/pemilik',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $other = $this->setupStore(User::create([
            'name' => 'Lain',
            'email' => 'lain@x.com',
            'role' => 'admin',
            'is_active' => true,
        ]));

        $this->actingAs($other->users()->first())
            ->put(route('admin.links.update', [$other, $link]), [
                'title' => 'Dibajak',
                'url' => 'https://contoh.com/dibajak',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('store_links', ['id' => $link->id, 'title' => 'Tautan Pemilik']);
    }

    public function test_pengunjung_tanpa_login_tidak_bisa_membuka_admin_tautan(): void
    {
        $store = $this->setupStore();

        $this->get(route('admin.links.index', $store))->assertRedirect('/login');
    }

    public function test_urutan_tautan_bisa_digeser(): void
    {
        $store = $this->setupStore();
        $admin = $store->users()->first();

        $first = StoreLink::create([
            'store_id' => $store->id, 'title' => 'Pertama',
            'url' => 'https://contoh.com/1', 'sort_order' => 1, 'is_active' => true,
        ]);
        $second = StoreLink::create([
            'store_id' => $store->id, 'title' => 'Kedua',
            'url' => 'https://contoh.com/2', 'sort_order' => 2, 'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.links.move', [$store, $second]), ['direction' => 'up'])
            ->assertRedirect();

        $this->assertSame(
            [$second->id, $first->id],
            StoreLink::forStore($store->id)->orderBy('sort_order')->pluck('id')->all()
        );

        // Baris paling atas tidak bisa naik lagi, dan nomor urut tetap unik.
        $this->actingAs($admin)
            ->patch(route('admin.links.move', [$store, $second]), ['direction' => 'up'])
            ->assertRedirect();

        $this->assertSame(
            [1, 2],
            StoreLink::forStore($store->id)->orderBy('sort_order')->pluck('sort_order')->all()
        );
    }
}
