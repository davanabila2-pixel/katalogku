<?php

namespace Tests\Feature;

use App\Models\ShippingRateCache;
use App\Models\Store;
use App\Models\User;
use App\Services\BiteshipService;
use App\Support\Couriers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminCourierSettingTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Toko',
            'email' => 'admin@toko.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->store = Store::create([
            'name' => 'Toko Busana Cantik',
            'slug' => 'toko-busana-cantik',
            'status' => 'active',
            'address' => 'Jl. Riau No. 10',
            'city' => 'Kota Bandung',
            'district' => 'Bandung Wetan',
            'province' => 'Jawa Barat',
            'postal_code' => '40115',
            'latitude' => -6.902481,
            'longitude' => 107.618622,
            'active_couriers' => ['jne', 'sicepat'],
        ]);

        $this->store->users()->attach($this->admin->id, ['is_primary' => true]);
    }

    public function test_admin_can_access_master_courier_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.couriers.index', $this->store));
        $response->assertOk();
        $response->assertSee('Master Ekspedisi & Cek Ongkir');
        $response->assertSee('Pilihan Ekspedisi Aktif (Master Kurir)');
        $response->assertSee('Alamat Asal Pengiriman Toko');
        $response->assertSee('Uji Coba Cek Ongkir Langsung');
        $response->assertSee('JNE Express');
        $response->assertSee('SiCepat Ekspres');
    }

    public function test_admin_can_update_active_couriers_and_origin_address(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.couriers.update', $this->store), [
            'active_couriers' => ['jne', 'sicepat', 'jnt', 'pos'],
            'address' => 'Jl. Dago Asri No. 25',
            'province' => 'Jawa Barat',
            'city' => 'Kota Bandung',
            'district' => 'Coblong',
            'postal_code' => '40135',
            'latitude' => -6.885123,
            'longitude' => 107.614567,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->store->refresh();
        $this->assertEquals(['jne', 'sicepat', 'jnt', 'pos'], $this->store->activeCourierCodes());
        $this->assertEquals('Jl. Dago Asri No. 25', $this->store->address);
        $this->assertEquals('Coblong', $this->store->district);
        $this->assertEquals('40135', $this->store->postal_code);
        $this->assertEquals(-6.885123, (float) $this->store->latitude);
        $this->assertEquals(107.614567, (float) $this->store->longitude);
    }

    public function test_admin_can_run_shipping_simulation(): void
    {
        $mockBiteship = Mockery::mock(BiteshipService::class);
        $mockBiteship->shouldReceive('checkRates')
            ->once()
            ->andReturn([
                'success' => true,
                'cached' => false,
                'rates' => [
                    [
                        'courier_name' => 'JNE',
                        'service_name' => 'Reguler',
                        'price' => 12000,
                        'etd' => '1-2 hari',
                    ],
                ],
            ]);

        $this->app->instance(BiteshipService::class, $mockBiteship);

        $response = $this->actingAs($this->admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.couriers.simulate', $this->store), [
                'destination_postal' => '12950',
                'destination_city' => 'Jakarta Selatan',
                'destination_district' => 'Setiabudi',
                'destination_province' => 'DKI Jakarta',
                'weight_grams' => 1000,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('rates.0.price', 12000);
    }

    public function test_admin_can_clear_store_shipping_cache(): void
    {
        ShippingRateCache::create([
            'cache_key' => 'test_key_postal_' . $this->store->postal_code,
            'origin_postal' => $this->store->postal_code,
            'destination_postal' => '12950',
            'weight_grams' => 1000,
            'rates' => [['price' => 15000]],
            'last_synced_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $this->assertEquals(1, ShippingRateCache::where('origin_postal', $this->store->postal_code)->count());

        $response = $this->actingAs($this->admin)->post(route('admin.couriers.clear_cache', $this->store));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(0, ShippingRateCache::where('origin_postal', $this->store->postal_code)->count());
    }

    public function test_unauthorized_user_cannot_access_or_update_couriers(): void
    {
        $otherAdmin = User::create([
            'name' => 'Admin Lain',
            'email' => 'other@toko.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($otherAdmin)->get(route('admin.couriers.index', $this->store));
        $response->assertForbidden();

        $responseUpdate = $this->actingAs($otherAdmin)->put(route('admin.couriers.update', $this->store), [
            'active_couriers' => ['jne'],
        ]);
        $responseUpdate->assertForbidden();
    }
}

