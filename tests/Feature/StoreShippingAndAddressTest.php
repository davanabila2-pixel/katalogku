<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreShippingAndAddressTest extends TestCase
{
    use RefreshDatabase;

    private function createStoreWithAdmin(string $name = 'Byvii Official', string $slug = 'byvii'): array
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $store = Store::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'contact_information' => '081234567890',
            'address' => 'Jl. Riau No. 50, Bandung',
            'postal_code' => '40115',
            'latitude' => -6.9034,
            'longitude' => 107.6186,
            'city' => 'Bandung',
            'district' => 'Bandung Wetan',
        ]);

        $admin->stores()->attach($store->id, ['is_primary' => true]);

        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Kategori Utama',
            'slug' => 'kategori-utama',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Kemeja Linen Premium',
            'slug' => 'kemeja-linen-premium',
            'selling_price' => 100000,
            'stock' => 15,
            'status' => 'active',
        ]);

        return [$admin, $store, $product];
    }

    public function test_customer_address_page_renders_for_authenticated_customer(): void
    {
        [$admin, $store, $product] = $this->createStoreWithAdmin();

        $customer = User::factory()->create([
            'role' => 'user',
            'name' => 'Citra Kirana',
        ]);

        $response = $this->actingAs($customer)->get(route('store.address', $store->slug));

        $response->assertOk();
        $response->assertSee('Alamat Saya di ' . $store->name);
        $response->assertSee('customerAddressForm');
        $response->assertSee('customerMapCanvas');
    }

    public function test_customer_can_update_address_with_coordinates_and_postal_code(): void
    {
        [$admin, $store, $product] = $this->createStoreWithAdmin();

        $customer = User::factory()->create([
            'role' => 'user',
            'name' => 'Citra Kirana',
        ]);

        $response = $this->actingAs($customer)->put(route('store.address.update', $store->slug), [
            'address' => 'Jl. Braga No. 10, RT 01 / RW 02',
            'postal_code' => '40111',
            'latitude' => -6.917543,
            'longitude' => 107.608321,
            'city' => 'Bandung',
            'district' => 'Sumur Bandung',
            'notes' => 'Pagar hitam, sebelah kafe',
        ]);

        $response->assertRedirect(route('store.address', $store->slug));
        $response->assertSessionHas('status', 'Alamat pengiriman Anda berhasil diperbarui.');

        $profile = StoreCustomerProfile::where('store_id', $store->id)
            ->where('user_id', $customer->id)
            ->first();

        $this->assertNotNull($profile);
        $this->assertEquals('Jl. Braga No. 10, RT 01 / RW 02', $profile->address);
        $this->assertEquals('40111', $profile->postal_code);
        $this->assertEquals(-6.917543, (float) $profile->latitude);
        $this->assertEquals(107.608321, (float) $profile->longitude);
        $this->assertEquals('Bandung', $profile->city);
        $this->assertEquals('Sumur Bandung', $profile->district);
        $this->assertTrue($profile->hasCoordinates());
    }

    public function test_store_admin_can_update_store_origin_address_and_coordinates(): void
    {
        [$admin, $store, $product] = $this->createStoreWithAdmin();

        $response = $this->actingAs($admin)->put(route('admin.profile.update', $store), [
            'name' => 'Byvii Official Updated',
            'address' => 'Jl. Dago No. 120, Coblong',
            'postal_code' => '40135',
            'latitude' => -6.885612,
            'longitude' => 107.614234,
            'city' => 'Bandung',
            'district' => 'Coblong',
        ]);

        $response->assertRedirect(route('admin.profile.edit', $store));

        $store->refresh();
        $this->assertEquals('Jl. Dago No. 120, Coblong', $store->address);
        $this->assertEquals('40135', $store->postal_code);
        $this->assertEquals(-6.885612, (float) $store->latitude);
        $this->assertEquals(107.614234, (float) $store->longitude);
        $this->assertEquals('Bandung', $store->city);
        $this->assertEquals('Coblong', $store->district);

        $origin = $store->originForBiteship();
        $this->assertEquals(-6.885612, $origin['latitude']);
        $this->assertEquals(107.614234, $origin['longitude']);
        $this->assertEquals('40135', $origin['postal_code']);
    }

    public function test_shipping_rates_api_validates_destination_input(): void
    {
        [$admin, $store, $product] = $this->createStoreWithAdmin();

        $response = $this->postJson(route('store.shipping.rates', $store->slug), [
            'postal_code' => '',
            'latitude' => '',
            'longitude' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment([
            'message' => 'Silakan lengkapi kode pos atau koordinat lokasi pengiriman pembeli untuk cek ongkir.',
        ]);
    }

    public function test_shipping_rates_api_returns_couriers_from_biteship(): void
    {
        [$admin, $store, $product] = $this->createStoreWithAdmin();

        Http::fake([
            'https://api.biteship.com/v1/rates/couriers' => Http::response([
                'success' => true,
                'pricing' => [
                    [
                        'company' => 'jne',
                        'courier_name' => 'JNE',
                        'courier_service_name' => 'Reguler',
                        'courier_service_code' => 'reg',
                        'type' => 'reg',
                        'service_type' => 'standard',
                        'price' => 11000,
                        'duration' => '1 - 2 hari',
                    ],
                    [
                        'company' => 'sicepat',
                        'courier_name' => 'SiCepat',
                        'courier_service_name' => 'BEST',
                        'courier_service_code' => 'best',
                        'type' => 'best',
                        'service_type' => 'express',
                        'price' => 15000,
                        'duration' => '1 hari',
                    ],
                ]
            ], 200),
        ]);

        $response = $this->postJson(route('store.shipping.rates', $store->slug), [
            'postal_code' => '40111',
            'latitude' => -6.917543,
            'longitude' => 107.608321,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json();
        $this->assertCount(2, $data['rates']);
        $this->assertEquals('JNE', $data['rates'][0]['courier_name']);
        $this->assertEquals(11000, $data['rates'][0]['price']);
        $this->assertEquals('SiCepat', $data['rates'][1]['courier_name']);
        $this->assertEquals(15000, $data['rates'][1]['price']);
    }

    public function test_checkout_saves_shipping_courier_and_calculates_total_price_with_shipping_fee(): void
    {
        [$admin, $store, $product] = $this->createStoreWithAdmin();

        $customer = User::factory()->create([
            'role' => 'user',
            'name' => 'Rina Wijaya',
            'email' => 'rina@example.com',
        ]);

        $response = $this->actingAs($customer)->postJson(route('cart.checkout', $store->slug), [
            'buyer_name' => 'Rina Wijaya',
            'buyer_phone' => '081298765432',
            'buyer_address' => 'Jl. Asia Afrika No. 100, Bandung',
            'buyer_postal_code' => '40112',
            'buyer_latitude' => -6.9214,
            'buyer_longitude' => 107.6105,
            'buyer_notes' => 'Tolong bubble wrap tebal',
            'shipping_courier' => 'JNE',
            'shipping_service' => 'Reguler',
            'shipping_fee' => 12000,
            'shipping_etd' => '1 - 2 hari',
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        // Subtotal: 100000 * 2 = 200000. Ongkir: 12000. Grand total: 212000.
        $order = Order::where('store_id', $store->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals(212000, (float) $order->total_price);
        $this->assertEquals('JNE', $order->shipping_courier);
        $this->assertEquals('Reguler', $order->shipping_service);
        $this->assertEquals(12000, (float) $order->shipping_fee);
        $this->assertEquals('1 - 2 hari', $order->shipping_etd);
        $this->assertEquals('40112', $order->buyer_postal_code);
        $this->assertEquals(-6.9214, (float) $order->buyer_latitude);
        $this->assertEquals(107.6105, (float) $order->buyer_longitude);

        // Profil pelanggan diperbarui
        $profile = StoreCustomerProfile::where('store_id', $store->id)
            ->where('user_id', $customer->id)
            ->first();

        $this->assertNotNull($profile);
        $this->assertEquals('40112', $profile->postal_code);
        $this->assertEquals(-6.9214, (float) $profile->latitude);
        $this->assertEquals(107.6105, (float) $profile->longitude);
    }
}

