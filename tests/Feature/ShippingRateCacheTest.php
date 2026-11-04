<?php

namespace Tests\Feature;

use App\Models\ShippingRateCache;
use App\Models\Store;
use App\Models\User;
use App\Services\BiteshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingRateCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.biteship.key', 'test-biteship-api-key');
        Config::set('services.biteship.cache_enabled', true);
        Config::set('services.biteship.cache_ttl_days', 1);
    }

    private function fakeBiteshipRatesResponse(): array
    {
        return [
            'success' => true,
            'object' => 'courier_pricing',
            'origin' => [
                'postal_code' => 60111,
                'city_name' => 'Surabaya',
                'district_name' => 'Gubeng',
                'country_name' => 'Indonesia',
            ],
            'destination' => [
                'postal_code' => 12340,
                'city_name' => 'Jakarta Selatan',
                'district_name' => 'Tebet',
                'country_name' => 'Indonesia',
            ],
            'pricing' => [
                [
                    'company' => 'jne',
                    'courier_name' => 'JNE',
                    'courier_service_name' => 'Reguler (REG)',
                    'courier_service_code' => 'reg',
                    'type' => 'reg',
                    'service_type' => 'standard',
                    'price' => 18000,
                    'duration' => '2-3 hari',
                    'description' => 'Layanan Reguler JNE',
                ],
                [
                    'company' => 'sicepat',
                    'courier_name' => 'SiCepat',
                    'courier_service_name' => 'SiUntung',
                    'courier_service_code' => 'siuntung',
                    'type' => 'reg',
                    'service_type' => 'standard',
                    'price' => 15000,
                    'duration' => '1-2 hari',
                    'description' => 'Layanan Reguler SiCepat',
                ],
            ],
        ];
    }

    public function test_check_rates_saves_to_database_on_first_request_and_reads_from_database_on_subsequent_request(): void
    {
        Http::fake([
            'https://api.biteship.com/v1/rates/couriers*' => Http::response($this->fakeBiteshipRatesResponse(), 200),
        ]);

        $biteship = app(BiteshipService::class);
        $origin = ['postal_code' => 60111, 'city' => 'Surabaya'];
        $destination = ['postal_code' => 12340, 'city' => 'Jakarta Selatan'];
        $items = [['name' => 'Kemeja Flannel', 'price' => 120000, 'quantity' => 2, 'weight' => 500]];

        // Panggilan ke-1: Belum ada di DB (Cache MISS) -> Hit API Biteship
        $res1 = $biteship->checkRates($origin, $destination, $items, 'jne,sicepat');

        $this->assertTrue($res1['success']);
        $this->assertFalse($res1['cached']);
        $this->assertCount(2, $res1['rates']);
        Http::assertSentCount(1);

        // Verifikasi tersimpan di tabel database shipping_rate_caches lengkap dengan struktur alamat & tarif
        $this->assertDatabaseCount('shipping_rate_caches', 1);
        $cacheRecord = ShippingRateCache::first();
        $this->assertNotNull($cacheRecord);
        $this->assertEquals('60111', (string) $cacheRecord->origin_postal);
        $this->assertEquals('12340', (string) $cacheRecord->destination_postal);
        $this->assertEquals('Surabaya', $cacheRecord->origin_city);
        $this->assertEquals('Jakarta Selatan', $cacheRecord->destination_city);
        $this->assertEquals(1000, $cacheRecord->weight_grams);
        $this->assertEquals(0, $cacheRecord->hit_count);
        $this->assertNotNull($cacheRecord->last_synced_at);
        $this->assertFalse($cacheRecord->isExpired());

        // Panggilan ke-2: Ada di DB (Cache HIT) -> Langsung return dari database, 0 API request ke Biteship!
        $res2 = $biteship->checkRates($origin, $destination, $items, 'jne,sicepat');

        $this->assertTrue($res2['success']);
        $this->assertTrue($res2['cached']);
        $this->assertCount(2, $res2['rates']);
        // Jumlah panggilan HTTP eksternal tetap 1 (TIDAK BERTAMBAH!)
        Http::assertSentCount(1);

        $cacheRecord->refresh();
        $this->assertEquals(1, $cacheRecord->hit_count);
    }

    public function test_couriers_order_does_not_affect_cache_hit(): void
    {
        Http::fake([
            'https://api.biteship.com/v1/rates/couriers*' => Http::response($this->fakeBiteshipRatesResponse(), 200),
        ]);

        $biteship = app(BiteshipService::class);
        $origin = ['postal_code' => 60111];
        $destination = ['postal_code' => 12340];
        $items = [['name' => 'Sepatu', 'price' => 200000, 'quantity' => 1, 'weight' => 1000]];

        // Hit dengan kurir 'jne,sicepat'
        $res1 = $biteship->checkRates($origin, $destination, $items, 'jne,sicepat');
        $this->assertFalse($res1['cached']);
        Http::assertSentCount(1);

        // Hit dengan urutan terbalik 'sicepat,jne' -> tetap harus HIT cache yang sama
        $res2 = $biteship->checkRates($origin, $destination, $items, 'sicepat,jne');
        $this->assertTrue($res2['cached']);
        Http::assertSentCount(1);
    }

    public function test_expired_cache_triggers_new_api_call_and_refreshes_cache(): void
    {
        Http::fake([
            'https://api.biteship.com/v1/rates/couriers*' => Http::response($this->fakeBiteshipRatesResponse(), 200),
        ]);

        $biteship = app(BiteshipService::class);
        $origin = ['postal_code' => 60111];
        $destination = ['postal_code' => 12340];
        $items = [['name' => 'Jaket', 'price' => 150000, 'quantity' => 1, 'weight' => 1000]];

        // Buat record cache yang sudah kedaluwarsa (> 1 hari) secara manual
        $cacheKey = $biteship->generateCacheKey([
            'origin_postal_code' => 60111,
            'destination_postal_code' => 12340,
            'couriers' => 'jne,sicepat',
        ], 1000);

        ShippingRateCache::create([
            'cache_key' => $cacheKey,
            'origin_postal' => '60111',
            'destination_postal' => '12340',
            'couriers' => 'jne,sicepat',
            'weight_grams' => 1000,
            'rates' => [['company' => 'jne', 'price' => 10000]],
            'last_synced_at' => now()->subDays(2),
            'expires_at' => now()->subMinutes(5), // Kedaluwarsa
        ]);

        // Karena sudah expired (> 1 hari), panggil API Biteship untuk update data ongkir terbaru
        $res = $biteship->checkRates($origin, $destination, $items, 'jne,sicepat');

        $this->assertTrue($res['success']);
        $this->assertFalse($res['cached']);
        Http::assertSentCount(1);

        // Record cache diperbarui dan masa berlaku diperpanjang 1 hari ke depan
        $cacheRecord = ShippingRateCache::where('cache_key', $cacheKey)->first();
        $this->assertFalse($cacheRecord->isExpired());
    }

    public function test_bypass_cache_flag_forces_fresh_api_call(): void
    {
        Http::fake([
            'https://api.biteship.com/v1/rates/couriers*' => Http::response($this->fakeBiteshipRatesResponse(), 200),
        ]);

        $biteship = app(BiteshipService::class);
        $origin = ['postal_code' => 60111];
        $destination = ['postal_code' => 12340];
        $items = [['name' => 'Produk', 'price' => 50000, 'quantity' => 1, 'weight' => 500]];

        // Hit pertama
        $biteship->checkRates($origin, $destination, $items, 'jne,sicepat');
        Http::assertSentCount(1);

        // Hit kedua dengan bypassCache = true
        $res2 = $biteship->checkRates($origin, $destination, $items, 'jne,sicepat', bypassCache: true);
        $this->assertFalse($res2['cached']);
        Http::assertSentCount(2);
    }

    public function test_shipping_refresh_rates_command_updates_outdated_rates(): void
    {
        Http::fake([
            'https://api.biteship.com/v1/rates/couriers*' => Http::response($this->fakeBiteshipRatesResponse(), 200),
        ]);

        // Buat record cache yang sudah hampir expired
        $biteship = app(BiteshipService::class);
        $cacheKey = $biteship->generateCacheKey([
            'origin_postal_code' => 60111,
            'destination_postal_code' => 12340,
            'couriers' => 'jne,sicepat',
        ], 1000);

        ShippingRateCache::create([
            'cache_key' => $cacheKey,
            'origin_postal' => '60111',
            'destination_postal' => '12340',
            'couriers' => 'jne,sicepat',
            'weight_grams' => 1000,
            'rates' => [['company' => 'jne', 'price' => 10000]],
            'last_synced_at' => now()->subDay(),
            'expires_at' => now()->addHour(), // Mendekati kedaluwarsa (< 6 jam)
        ]);

        Artisan::call('shipping:refresh-rates');

        Http::assertSentCount(1);

        $cacheRecord = ShippingRateCache::where('cache_key', $cacheKey)->first();
        // Tarif berhasil diperbarui dengan harga terbaru dari API
        $this->assertEquals(15000, $cacheRecord->rates[0]['price']);
    }

    public function test_artisan_prune_cache_command_deletes_only_expired_records(): void
    {
        // 1 active cache
        ShippingRateCache::create([
            'cache_key' => 'active-key',
            'origin_postal' => '60111',
            'destination_postal' => '12340',
            'weight_grams' => 500,
            'rates' => [],
            'expires_at' => now()->addDays(2),
        ]);

        // 1 expired cache
        ShippingRateCache::create([
            'cache_key' => 'expired-key',
            'origin_postal' => '60111',
            'destination_postal' => '12340',
            'weight_grams' => 500,
            'rates' => [],
            'expires_at' => now()->subDay(),
        ]);

        $this->assertDatabaseCount('shipping_rate_caches', 2);

        Artisan::call('shipping:prune-cache');

        $this->assertDatabaseCount('shipping_rate_caches', 1);
        $this->assertDatabaseHas('shipping_rate_caches', ['cache_key' => 'active-key']);
        $this->assertDatabaseMissing('shipping_rate_caches', ['cache_key' => 'expired-key']);
    }

    public function test_shipping_controller_endpoint_uses_database_cache(): void
    {
        Http::fake([
            'https://api.biteship.com/v1/rates/couriers*' => Http::response($this->fakeBiteshipRatesResponse(), 200),
        ]);

        $store = Store::create([
            'name' => 'Toko Hemat',
            'slug' => 'toko-hemat',
            'status' => 'active',
            'contact_information' => '081234567890',
            'address' => 'Jl. Asal No. 1, Surabaya',
            'postal_code' => '60111',
            'origin_postal_code' => '60111',
            'active_couriers' => ['jne', 'sicepat'],
        ]);

        // Request 1 ke endpoint shipping rates: Cache MISS -> Hit Biteship
        $response1 = $this->postJson("/{$store->slug}/shipping/rates", ['postal_code' => 12340]);
        $response1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cached', false)
            ->assertJsonCount(2, 'rates');

        Http::assertSentCount(1);

        // Request 2 ke endpoint yang sama -> langsung dilayani dari database (cached: true)!
        $response2 = $this->postJson("/{$store->slug}/shipping/rates", ['postal_code' => 12340]);
        $response2->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cached', true)
            ->assertJsonCount(2, 'rates');

        // Zero additional HTTP API calls to Biteship!
        Http::assertSentCount(1);
    }
}
