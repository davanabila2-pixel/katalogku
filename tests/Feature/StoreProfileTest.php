<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function createStoreWithAdmin(): array
    {
        $admin = User::create([
            'name' => 'Admin Toko',
            'email' => 'admin@tokoprofil.com',
            'password' => 'secret123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $store = Store::create([
            'name' => 'Toko Busana Cantik',
            'slug' => 'toko-busana-cantik',
            'description' => 'Toko busana wanita dan pria terlengkap di Indonesia.',
            'contact_information' => '081234567890',
            'address' => 'Jl. Kenari No. 10, Jakarta Pusat',
            'status' => 'active',
        ]);

        $store->users()->attach($admin->id, ['is_primary' => true]);

        return [$store, $admin];
    }

    public function test_halaman_profil_toko_dapat_diakses_dengan_desain_lengkap(): void
    {
        [$store, $admin] = $this->createStoreWithAdmin();

        $response = $this->actingAs($admin)->get(route('admin.profile.edit', $store));

        $response->assertOk();
        $response->assertSeeText('Identitas & Branding Toko', false);
        $response->assertSeeText('Alamat URL & Slug Katalog', false);
        $response->assertSeeText('Kontak & Lokasi Toko', false);
        $response->assertSeeText('QRIS & Pembayaran Statis Toko', false);
        $response->assertSeeText('Pengaturan Katalog & Visibilitas', false);
        $response->assertSeeText('Google Drive Storage', false);
        $response->assertSeeText($store->name, false);
        $response->assertSee($store->slug);
        $response->assertDontSeeText('LIVE PREVIEW');
        $response->assertDontSeeText('Pratinjau di Google Search');
    }

    public function test_update_profil_toko_berhasil(): void
    {
        [$store, $admin] = $this->createStoreWithAdmin();

        $response = $this->actingAs($admin)->put(route('admin.profile.update', $store), [
            'name' => 'Toko Busana Modern',
            'slug' => 'toko-busana-modern',
            'description' => 'Katalog fashion trendy masa kini.',
            'contact_information' => '0899999999',
            'address' => 'Bandung, Jawa Barat',
            'hide_component_products' => '1',
        ]);

        $response->assertRedirect(route('admin.profile.edit', $store));
        $response->assertSessionHas('status');

        $store->refresh();
        $this->assertSame('Toko Busana Modern', $store->name);
        $this->assertSame('toko-busana-modern', $store->slug);
        $this->assertSame('Katalog fashion trendy masa kini.', $store->description);
        $this->assertSame('0899999999', $store->contact_information);
        $this->assertSame('Bandung, Jawa Barat', $store->address);
        $this->assertTrue($store->hide_component_products);
    }

    public function test_admin_dapat_mengunggah_dan_menghapus_qr_statis_toko(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$store, $admin] = $this->createStoreWithAdmin();

        $qrFile = \Illuminate\Http\UploadedFile::fake()->image('qris-toko.png', 400, 400);

        $response = $this->actingAs($admin)->put(route('admin.profile.update', $store), [
            'name' => $store->name,
            'qr_image' => $qrFile,
            'qr_account_name' => 'QRIS Toko Busana',
        ]);

        $response->assertRedirect(route('admin.profile.edit', $store));

        $store->refresh();
        $this->assertNotNull($store->qr_image_path);
        $this->assertSame('QRIS Toko Busana', $store->qr_account_name);
        $this->assertTrue($store->hasStaticQr());
        $this->assertNotNull($store->qrImageUrl());

        // Test hapus QR
        $responseRemove = $this->actingAs($admin)->put(route('admin.profile.update', $store), [
            'name' => $store->name,
            'remove_qr' => '1',
        ]);

        $responseRemove->assertRedirect(route('admin.profile.edit', $store));

        $store->refresh();
        $this->assertNull($store->qr_image_path);
        $this->assertFalse($store->hasStaticQr());
    }

    public function test_admin_dapat_mengunggah_auto_crop_dan_menghapus_banner_toko(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        [$store, $admin] = $this->createStoreWithAdmin();

        // Buat file gambar vertikal (misal 600x1200) untuk menguji auto-crop
        $bannerFile = \Illuminate\Http\UploadedFile::fake()->image('banner-toko.jpg', 600, 1200);

        $response = $this->actingAs($admin)->put(route('admin.profile.update', $store), [
            'name' => $store->name,
            'banner' => $bannerFile,
        ]);

        $response->assertRedirect(route('admin.profile.edit', $store));

        $store->refresh();
        $this->assertNotNull($store->banner_path);
        $this->assertTrue($store->hasBanner());
        $this->assertNotNull($store->bannerUrl());

        // Halaman publik menampilkan banner pada hero
        $publicResponse = $this->get($store->url());
        $publicResponse->assertOk();
        $publicResponse->assertSee('has-store-banner', false);
        $publicResponse->assertSee($store->bannerUrl(), false);

        // Test hapus banner
        $responseRemove = $this->actingAs($admin)->put(route('admin.profile.update', $store), [
            'name' => $store->name,
            'remove_banner' => '1',
        ]);

        $responseRemove->assertRedirect(route('admin.profile.edit', $store));

        $store->refresh();
        $this->assertNull($store->banner_path);
        $this->assertFalse($store->hasBanner());
    }
}
