<?php

namespace Tests\Feature;

use App\Models\IndonesiaCity;
use App\Models\IndonesiaDistrict;
use App\Models\IndonesiaProvince;
use App\Models\Store;
use App\Models\StoreCustomerAddress;
use App\Models\User;
use Database\Seeders\IndonesiaWilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndonesiaWilayahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Jalankan seeder wilayah Indonesia
        $this->seed(IndonesiaWilayahSeeder::class);
    }

    public function test_seeder_populates_provinces_cities_and_districts(): void
    {
        $this->assertGreaterThanOrEqual(34, IndonesiaProvince::count());
        $this->assertGreaterThanOrEqual(500, IndonesiaCity::count());
        $this->assertGreaterThanOrEqual(7000, IndonesiaDistrict::count());

        $jabar = IndonesiaProvince::where('name', 'like', '%JAWA BARAT%')->first();
        $this->assertNotNull($jabar);

        $bandung = IndonesiaCity::where('province_id', $jabar->id)
            ->where('name', 'like', '%KOTA BANDUNG%')
            ->first();
        $this->assertNotNull($bandung);

        $districts = $bandung->districts;
        $this->assertNotEmpty($districts);
        $this->assertTrue($districts->contains('name', 'BANDUNG WETAN'));
    }

    public function test_wilayah_api_provinces_endpoint(): void
    {
        $response = $this->getJson('/api/wilayah/provinces');

        $response->assertOk()
            ->assertJsonStructure([
                '*' => ['id', 'name'],
            ]);

        $names = collect($response->json())->pluck('name');
        $this->assertTrue($names->contains('JAWA BARAT'));
        $this->assertTrue($names->contains('DKI JAKARTA'));
    }

    public function test_wilayah_api_cities_endpoint(): void
    {
        $jabar = IndonesiaProvince::where('name', 'like', '%JAWA BARAT%')->first();

        $response = $this->getJson("/api/wilayah/cities?province_id={$jabar->id}");

        $response->assertOk()
            ->assertJsonStructure([
                '*' => ['id', 'province_id', 'name'],
            ]);

        $cities = collect($response->json())->pluck('name');
        $this->assertTrue($cities->contains('KOTA BANDUNG'));
    }

    public function test_wilayah_api_districts_endpoint(): void
    {
        $bandung = IndonesiaCity::where('name', 'like', '%KOTA BANDUNG%')->first();

        $response = $this->getJson("/api/wilayah/districts?city_id={$bandung->id}");

        $response->assertOk()
            ->assertJsonStructure([
                '*' => ['id', 'city_id', 'name'],
            ]);

        $districts = collect($response->json())->pluck('name');
        $this->assertTrue($districts->contains('BANDUNG WETAN'));
    }

    public function test_wilayah_api_resolve_endpoint(): void
    {
        $response = $this->getJson('/api/wilayah/resolve?city=Kota+Bandung&district=Bandung+Wetan');

        $response->assertOk()
            ->assertJsonPath('province.name', 'JAWA BARAT')
            ->assertJsonPath('city.name', 'KOTA BANDUNG')
            ->assertJsonPath('district.name', 'BANDUNG WETAN');
    }

    public function test_customer_role_can_save_address_with_wilayah_data(): void
    {
        $store = Store::create([
            'name' => 'Toko Wilayah',
            'slug' => 'toko-wilayah',
            'status' => 'active',
        ]);

        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->postJson("/{$store->slug}/alamat", [
            'label' => 'Kantor',
            'recipient_name' => 'Budi Santoso',
            'phone' => '08123456789',
            'address' => 'Jl. Diponegoro No. 22',
            'province' => 'JAWA BARAT',
            'city' => 'KOTA BANDUNG',
            'district' => 'BANDUNG WETAN',
            'postal_code' => '40115',
            'is_default' => 1,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('store_customer_addresses', [
            'store_id' => $store->id,
            'user_id' => $customer->id,
            'province' => 'JAWA BARAT',
            'city' => 'KOTA BANDUNG',
            'district' => 'BANDUNG WETAN',
            'postal_code' => '40115',
        ]);
    }

    public function test_store_admin_role_can_update_store_with_wilayah_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $store = Store::create([
            'name' => 'Toko Admin',
            'slug' => 'toko-admin',
            'status' => 'active',
        ]);
        $admin->stores()->attach($store->id, ['is_primary' => true]);

        $response = $this->actingAs($admin)->put(route('admin.profile.update', $store), [
            'name' => 'Toko Admin Updated',
            'address' => 'Jl. Riau No. 10',
            'province' => 'JAWA BARAT',
            'city' => 'KOTA BANDUNG',
            'district' => 'BANDUNG WETAN',
            'postal_code' => '40115',
        ]);

        $response->assertRedirect();

        $store->refresh();
        $this->assertEquals('JAWA BARAT', $store->province);
        $this->assertEquals('KOTA BANDUNG', $store->city);
        $this->assertEquals('BANDUNG WETAN', $store->district);
    }

    public function test_superadmin_role_can_update_store_with_wilayah_data(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $store = Store::create([
            'name' => 'Toko Super',
            'slug' => 'toko-super',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superadmin)->put(route('superadmin.stores.update', $store), [
            'name' => 'Toko Super Wilayah',
            'status' => 'active',
            'address' => 'Jl. Pemuda No. 1',
            'province' => 'JAWA TIMUR',
            'city' => 'KOTA SURABAYA',
            'district' => 'GUBENG',
            'postal_code' => '60281',
        ]);

        $response->assertRedirect();

        $store->refresh();
        $this->assertEquals('JAWA TIMUR', $store->province);
        $this->assertEquals('KOTA SURABAYA', $store->city);
        $this->assertEquals('GUBENG', $store->district);
        $this->assertEquals('60281', $store->postal_code);
    }
}
