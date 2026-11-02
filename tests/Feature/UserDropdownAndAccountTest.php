<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDropdownAndAccountTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleStore(): Store
    {
        return Store::create([
            'name' => 'Byvii Fashion',
            'slug' => 'byvii',
            'slogan' => 'Secondhand, First Choice.',
            'description' => 'Toko pakaian thrift terkurasi.',
            'status' => 'active',
        ]);
    }

    public function test_guest_tidak_melihat_dropdown_user_di_appbar_toko(): void
    {
        $store = $this->createSampleStore();

        $response = $this->get('/' . $store->slug);

        $response->assertOk();
        $response->assertSee('Masuk');
        $response->assertDontSee('id="userStoreMenuBtn"', false);
        $response->assertDontSee('Profile Saya');
        $response->assertDontSee('Pesanan Saya');
    }

    public function test_user_login_melihat_preview_avatar_nama_dan_dropdown_di_appbar_toko(): void
    {
        $store = $this->createSampleStore();

        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
        ]);

        $response = $this->actingAs($user)->get('/' . $store->slug);

        $response->assertOk();
        $response->assertSee('user-dropdown-wrap', false);
        $response->assertSee('user-nav-avatar', false);
        $response->assertSee('Budi Santoso');
        $response->assertSee('Profile Saya');
        $response->assertSee('Pesanan Saya');
        $response->assertSee('Keluar');
    }

    public function test_admin_toko_melihat_menu_kelola_toko_di_dropdown(): void
    {
        $store = $this->createSampleStore();

        $admin = User::factory()->create([
            'name' => 'Owner Byvii',
            'email' => 'owner@byvii.test',
        ]);

        $admin->stores()->attach($store->id, ['is_primary' => true]);

        $response = $this->actingAs($admin)->get('/' . $store->slug);

        $response->assertOk();
        $response->assertSee('Kelola Toko');
        $response->assertSee(route('admin.dashboard', $store));
    }

    public function test_halaman_profil_saya_dapat_diakses_dan_diperbarui(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('user.profile'));
        $response->assertOk();
        $response->assertSee('Profil Saya');
        $response->assertSee('Budi Santoso');

        $updateResponse = $this->actingAs($user)->put(route('user.profile.update'), [
            'name' => 'Budi Pratama',
            'email' => 'budipratama@example.com',
        ]);

        $updateResponse->assertRedirect(route('user.profile'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Budi Pratama',
            'email' => 'budipratama@example.com',
        ]);
    }

    public function test_halaman_pesanan_saya_menampilkan_daftar_pesanan_user(): void
    {
        $store = $this->createSampleStore();

        $user = User::factory()->create([
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@example.com',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Cardigan Rajut Oversize',
            'slug' => 'cardigan-rajut-oversize',
            'selling_price' => 125000,
            'stock' => 5,
            'status' => 'active',
        ]);

        Order::create([
            'order_code' => 'ORD-BYVI-261102-0001',
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'product_name' => $product->name,
            'product_price' => 125000,
            'quantity' => 2,
            'total_price' => 250000,
            'buyer_name' => $user->name,
            'buyer_phone' => '081234567890',
            'buyer_address' => 'Jl. Mawar No. 10',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('user.orders'));

        $response->assertOk();
        $response->assertSee('Pesanan Saya');
        $response->assertSee('ORD-BYVI-261102-0001');
        $response->assertSee('Cardigan Rajut Oversize');
        $response->assertSee('Byvii Fashion');
        $response->assertSee('Menunggu Konfirmasi');
    }
}
