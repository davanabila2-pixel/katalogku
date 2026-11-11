<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoreCheckoutPageTest extends TestCase
{
    use RefreshDatabase;

    private function createStoreWithProduct(int $stock = 10, float $price = 75000): array
    {
        $owner = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $store = Store::create([
            'name' => 'Toko Byvii Modern',
            'slug' => 'byvii-modern',
            'status' => 'active',
            'contact_information' => '081234567890',
            'qr_account_name' => 'BCA Byvii Store',
        ]);

        $owner->stores()->attach($store->id, ['is_primary' => true]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Peralatan Hewan',
            'slug' => 'peralatan-hewan',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Smart Feeder Otomatis',
            'slug' => 'smart-feeder-otomatis',
            'selling_price' => $price,
            'stock' => $stock,
            'status' => 'active',
        ]);

        return [$owner, $store, $product];
    }

    public function test_checkout_page_shows_empty_state_when_cart_is_empty(): void
    {
        $user = User::factory()->create();
        [$owner, $store, $product] = $this->createStoreWithProduct(10, 50000);

        $response = $this->actingAs($user)->get("/{$store->slug}/checkout");

        $response->assertStatus(200);
        $response->assertSee('Keranjang Belanja Toko Kosong');
        $response->assertSee('Jelajahi Produk ' . $store->name);
    }

    public function test_guest_cannot_view_checkout_page_and_is_redirected_to_store_login(): void
    {
        [$owner, $store, $product] = $this->createStoreWithProduct(10, 50000);

        $response = $this->get("/{$store->slug}/checkout");

        $response->assertRedirect(route('store.login', $store->slug));
        $response->assertSessionHas('info');
    }

    public function test_guest_cannot_add_product_to_cart_and_receives_unauthenticated_401(): void
    {
        [$owner, $store, $product] = $this->createStoreWithProduct(10, 50000);

        $response = $this->postJson('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'unauthenticated' => true,
                'redirect' => route('store.login', $store->slug),
            ]);
    }

    public function test_guest_cannot_submit_checkout_and_receives_unauthenticated_401(): void
    {
        [$owner, $store, $product] = $this->createStoreWithProduct(10, 50000);

        $response = $this->postJson("/{$store->slug}/cart/checkout", [
            'product_id' => $product->id,
            'quantity' => 1,
            'buyer_name' => 'Tamu Pembeli',
            'buyer_phone' => '081234567890',
            'buyer_address' => 'Jl. Tamu No. 1, Jakarta',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'unauthenticated' => true,
                'redirect' => route('store.login', $store->slug),
            ]);
    }

    public function test_checkout_page_renders_cart_items_and_form(): void
    {
        $user = User::factory()->create([
            'name' => 'Rina Wijaya',
            'email' => 'rina@example.com',
        ]);
        [$owner, $store, $product] = $this->createStoreWithProduct(10, 50000);

        // Tambahkan item ke keranjang
        $this->actingAs($user)->postJson('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)->get("/{$store->slug}/checkout");

        $response->assertStatus(200);
        $response->assertSee('Checkout Pesanan');
        $response->assertSee($store->name);
        $response->assertSee('Smart Feeder Otomatis');
        $response->assertSee('Rp 100.000'); // 2 x 50.000
        $response->assertSee('Data Pemesan & Pengiriman', false);
        $response->assertSee('Rina Wijaya'); // Prefilled nama user
        $response->assertSee('Kirim Pesanan & Konfirmasi WA', false);
    }

    public function test_checkout_page_renders_direct_buy_product(): void
    {
        $user = User::factory()->create();
        [$owner, $store, $product] = $this->createStoreWithProduct(5, 120000);

        $response = $this->actingAs($user)->get("/{$store->slug}/checkout?product_id={$product->id}&quantity=2");

        $response->assertStatus(200);
        $response->assertSee('Checkout Pesanan');
        $response->assertSee('Smart Feeder Otomatis');
        $response->assertSee('Rp 240.000'); // 2 x 120.000
        $response->assertSee('name="product_id" value="' . $product->id . '"', false);
    }

    public function test_checkout_submission_via_ajax_creates_order_and_clears_cart(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        [$owner, $store, $product] = $this->createStoreWithProduct(10, 50000);

        // Tambah ke keranjang
        $this->actingAs($user)->postJson('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $fakeProof = UploadedFile::fake()->image('bukti_transfer.jpg', 600, 600);

        $response = $this->actingAs($user)->postJson("/{$store->slug}/cart/checkout", [
            'buyer_name' => 'Bambang Pamungkas',
            'buyer_phone' => '081299998888',
            'buyer_address' => 'Jl. Stadion Gelora No. 20, Jakarta Pusat',
            'buyer_notes' => 'Tolong dicek bukti QRIS terlampir',
            'payment_proof' => $fakeProof,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'has_proof' => true,
                'total_price_formatted' => 'Rp 150.000',
            ]);

        $this->assertDatabaseHas('orders', [
            'store_id' => $store->id,
            'buyer_name' => 'Bambang Pamungkas',
            'quantity' => 3,
            'total_price' => 150000,
            'status' => 'paid',
        ]);

        // Keranjang sudah bersih
        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);

        // Stok berkurang dari 10 ke 7
        $this->assertSame(7, $product->fresh()->stock);
    }

    public function test_direct_buy_submission_via_ajax_creates_order(): void
    {
        $user = User::factory()->create();
        [$owner, $store, $product] = $this->createStoreWithProduct(8, 80000);

        $response = $this->actingAs($user)->postJson("/{$store->slug}/cart/checkout", [
            'product_id' => $product->id,
            'quantity' => 2,
            'buyer_name' => 'Dewi Lestari',
            'buyer_phone' => '087812345678',
            'buyer_address' => 'Jl. Cihampelas No. 100, Bandung',
            'buyer_notes' => 'Kirim siang ya kak',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total_price_formatted' => 'Rp 160.000',
            ]);

        $this->assertDatabaseHas('orders', [
            'store_id' => $store->id,
            'buyer_name' => 'Dewi Lestari',
            'quantity' => 2,
            'total_price' => 160000,
        ]);

        // Stok berkurang dari 8 ke 6
        $this->assertSame(6, $product->fresh()->stock);
    }
}
