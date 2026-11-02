<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCustomerIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleStore(string $name, string $slug): Store
    {
        return Store::create([
            'name' => $name,
            'slug' => $slug,
            'slogan' => "Slogan {$name}",
            'description' => "Deskripsi {$name}",
            'status' => 'active',
        ]);
    }

    public function test_registrasi_pembeli_berhasil_dan_membuat_profil_toko_terisolasi(): void
    {
        $store = $this->createSampleStore('Byvii Fashion', 'byvii');

        $regPage = $this->get(route('store.register', $store->slug));
        $regPage->assertOk();
        $regPage->assertSee('Daftar Akun Pelanggan');
        $regPage->assertSee('Byvii Fashion');
        $regPage->assertSee('store-auth-card', false);
        $regPage->assertSee('store-auth-left', false);
        $regPage->assertSee('store-auth-right', false);
        $regPage->assertSee('btn-toggle-password', false);

        $response = $this->post(route('store.register.submit', $store->slug), [
            'name' => 'Adinda Putri',
            'email' => 'adinda@example.com',
            'phone' => '081234567890',
            'address' => 'Jl. Anggrek No. 12, Bandung',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('store.show', $store->slug));
        $this->assertAuthenticated();

        $user = User::where('email', 'adinda@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('user', $user->role);

        $profile = StoreCustomerProfile::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($profile);
        $this->assertEquals('Adinda Putri', $profile->name);
        $this->assertEquals('081234567890', $profile->phone);
        $this->assertEquals('Jl. Anggrek No. 12, Bandung', $profile->address);
    }

    public function test_login_pembeli_di_toko_berhasil(): void
    {
        $store = $this->createSampleStore('Byvii Fashion', 'byvii');

        $user = User::factory()->create([
            'email' => 'pelanggan@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'user',
        ]);

        $loginPage = $this->get(route('store.login', $store->slug));
        $loginPage->assertOk();
        $loginPage->assertSee('Masuk ke Byvii Fashion');
        $loginPage->assertSee('store-auth-card', false);
        $loginPage->assertSee('store-auth-left', false);
        $loginPage->assertSee('store-auth-right', false);
        $loginPage->assertSee('btn-toggle-password', false);
        $loginPage->assertSee('256-Bit SSL Enkripsi');

        $response = $this->post(route('store.login.submit', $store->slug), [
            'email' => 'pelanggan@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('store.show', $store->slug));
        $this->assertAuthenticatedAs($user);
    }

    public function test_profil_pelanggan_terisolasi_antar_toko(): void
    {
        $storeA = $this->createSampleStore('Toko Sepatu A', 'sepatu-a');
        $storeB = $this->createSampleStore('Toko Baju B', 'baju-b');

        $user = User::factory()->create([
            'name' => 'Budi Asli',
            'email' => 'budi@example.com',
        ]);

        // Simpan profil di Toko A
        $this->actingAs($user)->put(route('store.profile.update', $storeA->slug), [
            'name' => 'Budi Sneakers Head',
            'phone' => '08111111111',
            'address' => 'Jl. Sudirman No. 10 Jakarta',
            'notes' => 'Pagar warna hitam',
        ]);

        // Simpan profil di Toko B
        $this->actingAs($user)->put(route('store.profile.update', $storeB->slug), [
            'name' => 'Budi Vintage Casual',
            'phone' => '08222222222',
            'address' => 'Jl. Dago No. 45 Bandung',
            'notes' => 'Titip di satpam',
        ]);

        $profA = $user->customerProfileFor($storeA->id);
        $profB = $user->customerProfileFor($storeB->id);

        $this->assertEquals('Budi Sneakers Head', $profA->name);
        $this->assertEquals('08111111111', $profA->phone);
        $this->assertEquals('Jl. Sudirman No. 10 Jakarta', $profA->address);

        $this->assertEquals('Budi Vintage Casual', $profB->name);
        $this->assertEquals('08222222222', $profB->phone);
        $this->assertEquals('Jl. Dago No. 45 Bandung', $profB->address);

        // Di halaman Toko A, dropdown menampilkan nama Budi Sneakers Head
        $respA = $this->actingAs($user)->get('/' . $storeA->slug);
        $respA->assertSee('Budi Sneakers Head');
        $respA->assertDontSee('Budi Vintage Casual');

        // Di halaman Toko B, dropdown menampilkan nama Budi Vintage Casual
        $respB = $this->actingAs($user)->get('/' . $storeB->slug);
        $respB->assertSee('Budi Vintage Casual');
        $respB->assertDontSee('Budi Sneakers Head');
    }

    public function test_pesanan_pelanggan_terisolasi_per_toko(): void
    {
        $storeA = $this->createSampleStore('Toko Sepatu A', 'sepatu-a');
        $storeB = $this->createSampleStore('Toko Baju B', 'baju-b');

        $user = User::factory()->create(['name' => 'Rina']);

        $prodA = Product::create([
            'store_id' => $storeA->id,
            'name' => 'Sepatu Loafers Cokelat',
            'slug' => 'sepatu-loafers-cokelat',
            'selling_price' => 300000,
            'stock' => 10,
            'status' => 'active',
        ]);

        $prodB = Product::create([
            'store_id' => $storeB->id,
            'name' => 'Kemeja Flannel Merah',
            'slug' => 'kemeja-flannel-merah',
            'selling_price' => 150000,
            'stock' => 10,
            'status' => 'active',
        ]);

        $orderA = Order::create([
            'order_code' => 'ORD-SEPA-261102-0001',
            'store_id' => $storeA->id,
            'product_id' => $prodA->id,
            'user_id' => $user->id,
            'product_name' => $prodA->name,
            'product_price' => 300000,
            'quantity' => 1,
            'total_price' => 300000,
            'buyer_name' => 'Rina Toko A',
            'buyer_phone' => '08123456789',
            'buyer_address' => 'Jakarta',
            'status' => 'pending',
        ]);

        $orderB = Order::create([
            'order_code' => 'ORD-BAJU-261102-0002',
            'store_id' => $storeB->id,
            'product_id' => $prodB->id,
            'user_id' => $user->id,
            'product_name' => $prodB->name,
            'product_price' => 150000,
            'quantity' => 1,
            'total_price' => 150000,
            'buyer_name' => 'Rina Toko B',
            'buyer_phone' => '08987654321',
            'buyer_address' => 'Bandung',
            'status' => 'pending',
        ]);

        // Halaman pesanan di Toko A hanya melihat order A
        $respA = $this->actingAs($user)->get(route('store.orders', $storeA->slug));
        $respA->assertOk();
        $respA->assertSee('ORD-SEPA-261102-0001');
        $respA->assertSee('Sepatu Loafers Cokelat');
        $respA->assertDontSee('ORD-BAJU-261102-0002');
        $respA->assertDontSee('Kemeja Flannel Merah');

        // Halaman pesanan di Toko B hanya melihat order B
        $respB = $this->actingAs($user)->get(route('store.orders', $storeB->slug));
        $respB->assertOk();
        $respB->assertSee('ORD-BAJU-261102-0002');
        $respB->assertSee('Kemeja Flannel Merah');
        $respB->assertDontSee('ORD-SEPA-261102-0001');
        $respB->assertDontSee('Sepatu Loafers Cokelat');
    }

    public function test_keranjang_terisolasi_per_toko_melalui_query_param(): void
    {
        $storeA = $this->createSampleStore('Toko Sepatu A', 'sepatu-a');
        $storeB = $this->createSampleStore('Toko Baju B', 'baju-b');

        $user = User::factory()->create(['name' => 'Dani']);

        $prodA = Product::create([
            'store_id' => $storeA->id,
            'name' => 'Sepatu Slip On',
            'slug' => 'sepatu-slip-on',
            'selling_price' => 200000,
            'stock' => 10,
            'status' => 'active',
        ]);

        $prodB = Product::create([
            'store_id' => $storeB->id,
            'name' => 'Jaket Denim',
            'slug' => 'jaket-denim',
            'selling_price' => 350000,
            'stock' => 10,
            'status' => 'active',
        ]);

        // Masukkan item ke Toko A dan Toko B
        CartItem::create([
            'store_id' => $storeA->id,
            'user_id' => $user->id,
            'product_id' => $prodA->id,
            'quantity' => 2,
        ]);

        CartItem::create([
            'store_id' => $storeB->id,
            'user_id' => $user->id,
            'product_id' => $prodB->id,
            'quantity' => 1,
        ]);

        // GET /cart?store_id={storeA} -> hanya mengembalikan item Toko A
        $respA = $this->actingAs($user)->getJson('/cart?store_id=' . $storeA->id);
        $respA->assertOk();
        $respA->assertJsonPath('cart.total_quantity', 2);
        $respA->assertJsonPath('cart.grand_total', 400000);
        $respA->assertJsonCount(1, 'cart.stores');
        $respA->assertJsonPath('cart.stores.0.store_slug', 'sepatu-a');

        // GET /cart?store_id={storeB} -> hanya mengembalikan item Toko B
        $respB = $this->actingAs($user)->getJson('/cart?store_id=' . $storeB->id);
        $respB->assertOk();
        $respB->assertJsonPath('cart.total_quantity', 1);
        $respB->assertJsonPath('cart.grand_total', 350000);
        $respB->assertJsonCount(1, 'cart.stores');
        $respB->assertJsonPath('cart.stores.0.store_slug', 'baju-b');
    }

    public function test_checkout_keranjang_otomatis_memperbarui_profil_toko_pelanggan(): void
    {
        $store = $this->createSampleStore('Byvii Fashion', 'byvii');
        $user = User::factory()->create(['name' => 'Maya']);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Rok Plisket Hitam',
            'slug' => 'rok-plisket-hitam',
            'selling_price' => 85000,
            'stock' => 10,
            'status' => 'active',
        ]);

        CartItem::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->postJson(route('cart.checkout', $store->slug), [
            'buyer_name' => 'Maya Anggraeni',
            'buyer_phone' => '081299887766',
            'buyer_address' => 'Jl. Melati No. 8 Surabaya',
            'buyer_notes' => 'Tolong dibungkus rapi',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $profile = StoreCustomerProfile::where('store_id', $store->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($profile);
        $this->assertEquals('Maya Anggraeni', $profile->name);
        $this->assertEquals('081299887766', $profile->phone);
        $this->assertEquals('Jl. Melati No. 8 Surabaya', $profile->address);
        $this->assertEquals('Tolong dibungkus rapi', $profile->notes);
    }
}

