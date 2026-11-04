<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCustomerAddress;
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

    public function test_customer_can_manage_multiple_addresses_and_set_default(): void
    {
        [$admin, $store, $product] = $this->createStoreWithAdmin();

        $customer = User::factory()->create([
            'role' => 'user',
            'name' => 'Dian Sastro',
        ]);

        // 1. Tambah alamat pertama (Rumah)
        $resp1 = $this->actingAs($customer)->post(route('store.address.store', $store->slug), [
            'label' => 'Rumah',
            'recipient_name' => 'Dian Sastro',
            'phone' => '081234567890',
            'address' => 'Jl. Gandaria I No. 15, Kebayoran Baru',
            'postal_code' => '12130',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'latitude' => -6.2482,
            'longitude' => 106.7915,
            'notes' => 'Pagar abu-abu',
            'is_default' => 1,
        ]);
        $resp1->assertRedirect(route('store.address', $store->slug));

        // 2. Tambah alamat kedua (Kantor)
        $resp2 = $this->actingAs($customer)->post(route('store.address.store', $store->slug), [
            'label' => 'Kantor',
            'recipient_name' => 'Dian Sastro (Kantor)',
            'phone' => '081987654321',
            'address' => 'Gedung Menara Sudirman Lt. 18, Jl. Jend. Sudirman Kav. 60',
            'postal_code' => '12190',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'latitude' => -6.2235,
            'longitude' => 106.8091,
            'notes' => 'Titip di resepsionis lobi',
            'is_default' => 0,
        ]);
        $resp2->assertRedirect(route('store.address', $store->slug));

        $addresses = StoreCustomerAddress::where('store_id', $store->id)
            ->where('user_id', $customer->id)
            ->get();
        $this->assertCount(2, $addresses);

        $addrHome = $addresses->firstWhere('label', 'Rumah');
        $addrOffice = $addresses->firstWhere('label', 'Kantor');
        $this->assertTrue($addrHome->is_default);
        $this->assertFalse($addrOffice->is_default);

        // Halaman Alamat Saya menampilkan kedua alamat
        $pageResp = $this->actingAs($customer)->get(route('store.address', $store->slug));
        $pageResp->assertOk();
        $pageResp->assertSee('Rumah');
        $pageResp->assertSee('Kantor');
        $pageResp->assertSee('Jl. Gandaria I No. 15');
        $pageResp->assertSee('Gedung Menara Sudirman');

        // 3. Set Kantor sebagai alamat utama
        $setDefaultResp = $this->actingAs($customer)->post(route('store.address.set_default', [$store->slug, $addrOffice->id]));
        $setDefaultResp->assertRedirect(route('store.address', $store->slug));

        $addrOffice->refresh();
        $addrHome->refresh();
        $this->assertTrue($addrOffice->is_default);
        $this->assertFalse($addrHome->is_default);

        // 4. Ubah detail alamat
        $updateResp = $this->actingAs($customer)->put(route('store.address.update_item', [$store->slug, $addrOffice->id]), [
            'label' => 'Kantor Pusat',
            'recipient_name' => 'Dian Sastro (HQ)',
            'phone' => '081987654321',
            'address' => 'Gedung Menara Sudirman Lt. 20, Jl. Jend. Sudirman Kav. 60',
            'postal_code' => '12190',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'latitude' => -6.2235,
            'longitude' => 106.8091,
            'notes' => 'Lantai 20 unit B',
        ]);
        $updateResp->assertRedirect(route('store.address', $store->slug));
        $addrOffice->refresh();
        $this->assertEquals('Kantor Pusat', $addrOffice->label);
        $this->assertEquals('Dian Sastro (HQ)', $addrOffice->recipient_name);

        // 5. Hapus alamat
        $delResp = $this->actingAs($customer)->delete(route('store.address.destroy', [$store->slug, $addrHome->id]));
        $delResp->assertRedirect(route('store.address', $store->slug));
        $this->assertDatabaseMissing('store_customer_addresses', ['id' => $addrHome->id]);
    }

    public function test_checkout_page_auto_populates_from_saved_addresses_and_renders_address_selector(): void
    {
        [$admin, $store, $product] = $this->createStoreWithAdmin();

        $customer = User::factory()->create([
            'role' => 'user',
            'name' => 'Reza Rahadian',
        ]);

        // Simpan 2 alamat pelanggan
        $addr1 = StoreCustomerAddress::create([
            'store_id' => $store->id,
            'user_id' => $customer->id,
            'label' => 'Apartemen',
            'recipient_name' => 'Reza Rahadian',
            'phone' => '081299998888',
            'address' => 'Apartemen Sudirman Tower A No. 1205',
            'postal_code' => '10220',
            'city' => 'Jakarta Pusat',
            'district' => 'Tanah Abang',
            'latitude' => -6.2088,
            'longitude' => 106.8225,
            'notes' => 'Titip di concierge',
            'is_default' => true,
        ]);

        $addr2 = StoreCustomerAddress::create([
            'store_id' => $store->id,
            'user_id' => $customer->id,
            'label' => 'Studio',
            'recipient_name' => 'Reza Rahadian (Studio)',
            'phone' => '081277776666',
            'address' => 'Jl. Kemang Raya No. 45',
            'postal_code' => '12730',
            'city' => 'Jakarta Selatan',
            'district' => 'Mampang Prapatan',
            'latitude' => -6.2625,
            'longitude' => 106.8155,
            'notes' => 'Pintu gerbang hitam',
            'is_default' => false,
        ]);

        // Akses checkout langsung produk
        $checkoutUrl = route('store.checkout', $store->slug) . "?product_id={$product->id}&quantity=1";
        $response = $this->actingAs($customer)->get($checkoutUrl);

        $response->assertOk();
        // Memastikan alamat default otomatis terisi di form
        $response->assertSee('Apartemen Sudirman Tower A No. 1205');
        $response->assertSee('10220');
        $response->assertSee('Reza Rahadian');
        $response->assertSee('081299998888');
        $response->assertSee('-6.2088');
        $response->assertSee('106.8225');

        // Memastikan dropdown multiple addresses ter-render
        $response->assertSee('checkoutAddressSelect');
        $response->assertSee('Pilih Alamat Pengiriman Saya');
        $response->assertSee('Studio');
        $response->assertSee('Jl. Kemang Raya No. 45');
    }
}

