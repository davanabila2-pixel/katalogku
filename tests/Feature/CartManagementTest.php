<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createStoreWithProduct(int $stock = 10, float $price = 50000): array
    {
        $owner = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $store = Store::create([
            'name' => 'Toko Elektronik Hebat',
            'slug' => 'toko-elektronik-hebat',
            'status' => 'active',
            'contact_information' => '081234567890',
            'qr_account_name' => 'BCA Toko Elektronik',
        ]);

        $owner->stores()->attach($store->id, ['is_primary' => true]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Aksesoris',
            'slug' => 'aksesoris',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Kabel Data Type C',
            'slug' => 'kabel-data-type-c',
            'selling_price' => $price,
            'stock' => $stock,
            'status' => 'active',
        ]);

        return [$owner, $store, $product];
    }

    public function test_user_can_add_product_to_cart_and_get_cart_data(): void
    {
        $user = User::factory()->create();
        [$owner, $store, $product] = $this->createStoreWithProduct(10, 50000);

        $response = $this->actingAs($user)->postJson('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'cart' => [
                    'items_count' => 1,
                    'total_quantity' => 2,
                    'grand_total' => 100000,
                ],
            ]);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'store_id' => $store->id,
            'quantity' => 2,
        ]);
    }

    public function test_cannot_add_out_of_stock_product_to_cart(): void
    {
        $user = User::factory()->create();
        [$owner, $store, $product] = $this->createStoreWithProduct(0, 50000); // Stok 0

        $response = $this->actingAs($user)->postJson('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_cart_items_are_persisted_per_user_account(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        [$owner, $store, $product] = $this->createStoreWithProduct(10, 50000);

        // User 1 adds item
        $this->actingAs($user1)->postJson('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        // User 2 opens cart -> empty
        $response2 = $this->actingAs($user2)->getJson('/cart');
        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'cart' => [
                    'items_count' => 0,
                    'total_quantity' => 0,
                ],
            ]);

        // User 1 opens cart -> has 3
        $response1 = $this->actingAs($user1)->getJson('/cart');
        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'cart' => [
                    'items_count' => 1,
                    'total_quantity' => 3,
                ],
            ]);
    }

    public function test_user_can_update_quantity_and_remove_item(): void
    {
        $user = User::factory()->create();
        [$owner, $store, $product] = $this->createStoreWithProduct(10, 50000);

        $this->actingAs($user)->postJson('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $cartItem = CartItem::where('user_id', $user->id)->first();

        // Update quantity to 4
        $updateResp = $this->actingAs($user)->patchJson("/cart/items/{$cartItem->id}", [
            'quantity' => 4,
        ]);
        $updateResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'cart' => [
                    'total_quantity' => 4,
                ],
            ]);

        $this->assertSame(4, $cartItem->fresh()->quantity);

        // Delete item
        $deleteResp = $this->actingAs($user)->deleteJson("/cart/items/{$cartItem->id}");
        $deleteResp->assertStatus(200);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    public function test_cannot_checkout_cart_when_stock_is_exhausted(): void
    {
        $user = User::factory()->create();
        [$owner, $store, $product] = $this->createStoreWithProduct(2, 50000);

        $this->actingAs($user)->postJson('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        // Ubah stok produk menjadi 0 (habis dibeli orang lain)
        $product->update(['stock' => 0]);

        $response = $this->actingAs($user)->postJson("/{$store->slug}/cart/checkout", [
            'buyer_name' => 'Budi Santoso',
            'buyer_phone' => '081234567890',
            'buyer_address' => 'Jl. Sudirman No. 1, Jakarta',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseMissing('orders', [
            'buyer_name' => 'Budi Santoso',
        ]);
    }

    public function test_user_can_checkout_store_cart_successfully(): void
    {
        $user = User::factory()->create();
        [$owner, $store, $product] = $this->createStoreWithProduct(10, 50000);

        $this->actingAs($user)->postJson('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($user)->postJson("/{$store->slug}/cart/checkout", [
            'buyer_name' => 'Anita Rahayu',
            'buyer_phone' => '087788990011',
            'buyer_address' => 'Jl. Melati No. 8, Bandung, Jawa Barat',
            'buyer_notes' => 'Bungkus rapi',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'total_price_formatted' => 'Rp 150.000',
            ]);

        // Order tersimpan di tabel orders
        $this->assertDatabaseHas('orders', [
            'store_id' => $store->id,
            'buyer_name' => 'Anita Rahayu',
            'quantity' => 3,
            'total_price' => 150000,
        ]);

        // Stok berkurang 3 (dari 10 menjadi 7)
        $this->assertSame(7, $product->fresh()->stock);

        // Keranjang toko tersebut sudah kosong
        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);
    }

    public function test_direct_buy_fails_when_product_is_out_of_stock(): void
    {
        [$owner, $store, $product] = $this->createStoreWithProduct(0, 75000); // Stok 0

        $response = $this->postJson("/{$store->slug}/orders", [
            'product_id' => $product->id,
            'quantity' => 1,
            'buyer_name' => 'Candra Wijaya',
            'buyer_phone' => '081299887766',
            'buyer_address' => 'Jl. Pahlawan No. 20, Surabaya',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseMissing('orders', [
            'buyer_name' => 'Candra Wijaya',
        ]);
    }
}
