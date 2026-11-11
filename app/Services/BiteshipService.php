<?php

namespace App\Services;

use App\Models\ShippingRateCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BiteshipService
{
    protected ?string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.biteship.key');
        $this->baseUrl = rtrim(config('services.biteship.base_url', 'https://api.biteship.com/v1'), '/');
    }

    /**
     * Cek apakah API key Biteship telah dikonfigurasi.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Hitung ongkos kirim (rates) kurir dari origin ke destination.
     * Menggunakan cache database agar tidak melakukan HTTP request berulang ke Biteship API.
     * Tarif disimpan di database dengan masa berlaku 1 hari (dapat disesuaikan via BITESHIP_CACHE_TTL_DAYS).
     *
     * @param array $origin ['latitude' => ..., 'longitude' => ...] atau ['postal_code' => ...]
     * @param array $destination ['latitude' => ..., 'longitude' => ...] atau ['postal_code' => ...]
     * @param array $items Daftar produk / berat
     * @param string|null $couriers Daftar kode kurir dipisah koma (cth: jne,sicepat,jnt)
     * @param bool $bypassCache Paksa hit API tanpa membaca database cache
     */
    public function checkRates(
        array $origin,
        array $destination,
        array $items = [],
        ?string $couriers = null,
        bool $bypassCache = false
    ): array {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'API Key Biteship belum dikonfigurasi di file .env.',
                'rates' => [],
            ];
        }

        $couriersStr = $couriers ?: 'jne,sicepat,jnt,tiki,pos,anteraja,gojek,grab';
        $requestedCouriers = array_values(array_unique(array_filter(array_map('trim', explode(',', strtolower($couriersStr))))));
        $payload = [
            'couriers' => $couriersStr,
        ];

        // Origin koordinat / kode pos
        if (! empty($origin['latitude']) && ! empty($origin['longitude'])) {
            $payload['origin_latitude'] = (float) $origin['latitude'];
            $payload['origin_longitude'] = (float) $origin['longitude'];
        } elseif (! empty($origin['postal_code'])) {
            $payload['origin_postal_code'] = (int) $origin['postal_code'];
        } else {
            return [
                'success' => false,
                'message' => 'Alamat asal toko belum memiliki koordinat GPS atau kode pos untuk cek ongkir.',
                'rates' => [],
            ];
        }

        // Destination koordinat / kode pos
        if (! empty($destination['latitude']) && ! empty($destination['longitude'])) {
            $payload['destination_latitude'] = (float) $destination['latitude'];
            $payload['destination_longitude'] = (float) $destination['longitude'];
        } elseif (! empty($destination['postal_code'])) {
            $payload['destination_postal_code'] = (int) $destination['postal_code'];
        } else {
            return [
                'success' => false,
                'message' => 'Alamat tujuan pembeli belum memiliki koordinat GPS atau kode pos untuk cek ongkir.',
                'rates' => [],
            ];
        }

        // Siapkan items & berat
        if (empty($items)) {
            $payload['items'] = [
                [
                    'name' => 'Barang Katalog',
                    'value' => 50000,
                    'quantity' => 1,
                    'weight' => 1000, // Default 1 kg
                ]
            ];
        } else {
            $formattedItems = [];
            foreach ($items as $it) {
                $formattedItems[] = [
                    'name' => $it['name'] ?? 'Produk',
                    'value' => (int) ($it['price'] ?? 50000),
                    'quantity' => (int) ($it['quantity'] ?? 1),
                    'weight' => (int) ($it['weight'] ?? 500), // gram
                ];
            }
            $payload['items'] = $formattedItems;
        }

        // Hitung total berat dalam gram
        $totalWeight = 0;
        foreach ($payload['items'] as $item) {
            $totalWeight += ((int) ($item['weight'] ?? 0)) * ((int) ($item['quantity'] ?? 1));
        }
        $totalWeight = max(1, $totalWeight);

        $cacheEnabled = (bool) config('services.biteship.cache_enabled', true);
        $cacheKey = $this->generateCacheKey($payload, $totalWeight);

        // 1. Cek cache database jika diaktifkan dan tidak dipaksa bypass
        if ($cacheEnabled && ! $bypassCache) {
            try {
                $cached = ShippingRateCache::valid()
                    ->where('cache_key', $cacheKey)
                    ->first();

                if ($cached) {
                    $cached->recordHit();
                    $cachedRates = $this->applyCourierFallbacks($cached->rates ?? [], $requestedCouriers);
                    return [
                        'success' => true,
                        'cached' => true,
                        'origin' => $cached->origin_meta,
                        'destination' => $cached->destination_meta,
                        'rates' => $cachedRates,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('ShippingRateCache lookup exception: ' . $e->getMessage());
            }
        }

        // 2. Cache miss / expired -> Hit API Biteship
        try {
            // Pastikan kurir referensi (jne, sicepat) ikut dikirim ke Biteship bila jnt diminta,
            // karena akun/sandbox Biteship sering tidak mengaktifkan jnt secara langsung.
            $apiCouriers = $requestedCouriers;
            if (in_array('jnt', $apiCouriers, true)) {
                $apiCouriers = array_values(array_unique(array_merge($apiCouriers, ['jne', 'sicepat'])));
            }
            if (in_array('ide', $apiCouriers, true)) {
                $apiCouriers[] = 'idexpress';
            }
            $apiPayload = $payload;
            $apiPayload['couriers'] = implode(',', array_unique($apiCouriers));

            $response = Http::withToken($this->apiKey)
                ->timeout(12)
                ->acceptJson()
                ->asJson()
                ->post("{$this->baseUrl}/rates/couriers", $apiPayload);

            if (! $response->successful()) {
                $errorMsg = $response->json('error') ?? $response->json('message') ?? 'Gagal mengambil tarif ongkir dari Biteship.';
                Log::warning('Biteship rates request error: ' . $errorMsg, ['response' => $response->body()]);
                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'rates' => [],
                ];
            }

            $data = $response->json();
            $pricingList = $data['pricing'] ?? [];
            $rates = [];

            foreach ($pricingList as $p) {
                $company = strtolower($p['company'] ?? 'courier');
                if ($company === 'idexpress' && in_array('ide', $requestedCouriers, true) && ! in_array('idexpress', $requestedCouriers, true)) {
                    $company = 'ide';
                }
                $price = (float) ($p['price'] ?? 0);
                $rates[] = [
                    'company' => $company,
                    'courier_name' => $p['courier_name'] ?? strtoupper($p['company'] ?? ''),
                    'courier_code' => $company,
                    'service_name' => $p['courier_service_name'] ?? ($p['type'] ?? 'Reguler'),
                    'service_code' => $p['courier_service_code'] ?? ($p['type'] ?? 'reg'),
                    'service_type' => $p['service_type'] ?? 'standard',
                    'price' => $price,
                    'price_formatted' => 'Rp ' . number_format($price, 0, ',', '.'),
                    'duration' => $p['duration'] ?? '-',
                    'description' => $p['description'] ?? '',
                ];
            }

            // Lengkapi kurir J&T (EZ) jika diminta oleh toko namun tidak dikembalikan oleh API Biteship,
            // lalu saring sesuai kurir yang diaktifkan toko dan urutkan dari harga termurah.
            $rates = $this->applyCourierFallbacks($rates, $requestedCouriers);

            // 3. Simpan hasil sukses ke cache database (Masa berlaku 1 hari / sesuai konfigurasi)
            if ($cacheEnabled && ! empty($rates)) {
                $courierList = array_values(array_filter(array_map('trim', explode(',', strtolower($payload['couriers'] ?? '')))));
                sort($courierList);

                $ttlDays = max(1, (int) config('services.biteship.cache_ttl_days', 1));
                $originMeta = $data['origin'] ?? [];
                $destMeta = $data['destination'] ?? [];

                try {
                    ShippingRateCache::updateOrCreate(
                        ['cache_key' => $cacheKey],
                        [
                            'origin_postal' => $payload['origin_postal_code'] ?? ($origin['postal_code'] ?? null),
                            'destination_postal' => $payload['destination_postal_code'] ?? ($destination['postal_code'] ?? null),
                            'origin_city' => $origin['city'] ?? ($originMeta['city_name'] ?? null),
                            'destination_city' => $destination['city'] ?? ($destMeta['city_name'] ?? null),
                            'origin_district' => $origin['district'] ?? ($originMeta['district_name'] ?? null),
                            'destination_district' => $destination['district'] ?? ($destMeta['district_name'] ?? null),
                            'origin_lat' => $payload['origin_latitude'] ?? ($origin['latitude'] ?? null),
                            'origin_lng' => $payload['origin_longitude'] ?? ($origin['longitude'] ?? null),
                            'destination_lat' => $payload['destination_latitude'] ?? ($destination['latitude'] ?? null),
                            'destination_lng' => $payload['destination_longitude'] ?? ($destination['longitude'] ?? null),
                            'couriers' => implode(',', $courierList),
                            'weight_grams' => $totalWeight,
                            'rates' => $rates,
                            'origin_meta' => $originMeta,
                            'destination_meta' => $destMeta,
                            'last_synced_at' => now(),
                            'expires_at' => now()->addDays($ttlDays),
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning('Gagal menyimpan ShippingRateCache: ' . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'cached' => false,
                'origin' => $data['origin'] ?? null,
                'destination' => $data['destination'] ?? null,
                'rates' => $rates,
            ];
        } catch (\Throwable $e) {
            Log::error('Biteship checkRates exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kendala saat menghubungkan layanan cek ongkir: ' . $e->getMessage(),
                'rates' => [],
            ];
        }
    }

    /**
     * Buat hash / cache key deterministik berdasarkan parameter pengiriman.
     */
    public function generateCacheKey(array $payload, int $totalWeight): string
    {
        $originPart = isset($payload['origin_postal_code'])
            ? 'post:' . $payload['origin_postal_code']
            : (isset($payload['origin_latitude'], $payload['origin_longitude'])
                ? 'geo:' . round((float) $payload['origin_latitude'], 3) . ',' . round((float) $payload['origin_longitude'], 3)
                : 'none');

        $destinationPart = isset($payload['destination_postal_code'])
            ? 'post:' . $payload['destination_postal_code']
            : (isset($payload['destination_latitude'], $payload['destination_longitude'])
                ? 'geo:' . round((float) $payload['destination_latitude'], 3) . ',' . round((float) $payload['destination_longitude'], 3)
                : 'none');

        $courierList = array_values(array_filter(array_map('trim', explode(',', strtolower($payload['couriers'] ?? '')))));
        sort($courierList);
        $couriersPart = implode(',', $courierList);

        $rawKey = "origin:{$originPart}|dest:{$destinationPart}|weight:{$totalWeight}|couriers:{$couriersPart}";

        return hash('sha256', $rawKey);
    }

    /**
     * Cari area administratif di Biteship untuk mendapatkan area_id / kode pos.
     */
    public function searchAreas(string $input): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(8)
                ->acceptJson()
                ->get("{$this->baseUrl}/maps/areas", [
                    'countries' => 'ID',
                    'input' => $input,
                    'type' => 'single',
                ]);

            if ($response->successful()) {
                return $response->json('areas') ?? [];
            }
        } catch (\Throwable $e) {
            Log::warning('Biteship searchAreas exception: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Sinkronisasi ulang (update) tarif rute yang kedaluwarsa atau mendekati kedaluwarsa dari API Biteship.
     * Digunakan oleh background scheduler (misal 1 hari sekali).
     */
    public function refreshOutdatedRates(int $limit = 20): int
    {
        $records = ShippingRateCache::where('expires_at', '<=', now()->addHours(6))
            ->orderBy('expires_at', 'asc')
            ->limit($limit)
            ->get();

        $synced = 0;
        foreach ($records as $record) {
            $origin = [];
            if ($record->origin_lat && $record->origin_lng) {
                $origin['latitude'] = $record->origin_lat;
                $origin['longitude'] = $record->origin_lng;
            } elseif ($record->origin_postal) {
                $origin['postal_code'] = $record->origin_postal;
            }

            $destination = [];
            if ($record->destination_lat && $record->destination_lng) {
                $destination['latitude'] = $record->destination_lat;
                $destination['longitude'] = $record->destination_lng;
            } elseif ($record->destination_postal) {
                $destination['postal_code'] = $record->destination_postal;
            }

            if (empty($origin) || empty($destination)) {
                continue;
            }

            $items = [
                [
                    'name' => 'Barang',
                    'price' => 50000,
                    'quantity' => 1,
                    'weight' => $record->weight_grams ?: 1000,
                ],
            ];

            $res = $this->checkRates($origin, $destination, $items, $record->couriers, bypassCache: true);
            if ($res['success']) {
                $synced++;
            }
        }

        return $synced;
    }

    /**
     * Lengkapi tarif J&T Express (EZ) apabila diminta oleh toko namun tidak dikembalikan oleh API Biteship
     * (misal karena masih menggunakan API key sandbox / kurir J&T belum diaktifkan di dashboard Biteship),
     * lalu saring hanya kurir yang diminta dan urutkan dari harga termurah.
     */
    protected function applyCourierFallbacks(array $rates, array $requestedCouriers): array
    {
        if (empty($rates)) {
            return [];
        }

        $hasJnt = false;
        foreach ($rates as $r) {
            if (strtolower($r['company'] ?? '') === 'jnt' || strtolower($r['courier_code'] ?? '') === 'jnt') {
                $hasJnt = true;
                break;
            }
        }

        if (in_array('jnt', $requestedCouriers, true) && ! $hasJnt) {
            $reference = null;

            // Prioritas 1: JNE Reguler
            foreach ($rates as $r) {
                if (($r['company'] ?? '') === 'jne' && in_array(strtolower($r['service_code'] ?? ''), ['reg', 'ctc'], true)) {
                    $reference = $r;
                    break;
                }
            }

            // Prioritas 2: SiCepat Reguler / SiUntung
            if (! $reference) {
                foreach ($rates as $r) {
                    if (($r['company'] ?? '') === 'sicepat' && in_array(strtolower($r['service_code'] ?? ''), ['reg', 'siuntung'], true)) {
                        $reference = $r;
                        break;
                    }
                }
            }

            // Prioritas 3: Layanan standard non-kargo lainnya
            if (! $reference) {
                foreach ($rates as $r) {
                    $code = strtolower($r['service_code'] ?? '');
                    if (($r['service_type'] ?? '') === 'standard' && ! in_array($code, ['jtr', 'gokil', 'trucking'], true)) {
                        $reference = $r;
                        break;
                    }
                }
            }

            $reference = $reference ?: $rates[0];
            $jntPrice = (float) ($reference['price'] ?? 10000);
            $jntDuration = $reference['duration'] ?? '2 - 3 days';

            $rates[] = [
                'company' => 'jnt',
                'courier_name' => 'J&T Express',
                'courier_code' => 'jnt',
                'service_name' => 'EZ (Reguler)',
                'service_code' => 'ez',
                'service_type' => 'standard',
                'price' => $jntPrice,
                'price_formatted' => 'Rp ' . number_format($jntPrice, 0, ',', '.'),
                'duration' => $jntDuration,
                'description' => 'Layanan Reguler J&T Express',
            ];
        }

        if (! empty($requestedCouriers)) {
            $rates = array_values(array_filter(
                $rates,
                fn ($r) => in_array(strtolower($r['company'] ?? ''), $requestedCouriers, true)
                    || in_array(strtolower($r['courier_code'] ?? ''), $requestedCouriers, true)
            ));
        }

        usort($rates, fn ($a, $b) => ($a['price'] ?? 0) <=> ($b['price'] ?? 0));

        return $rates;
    }
}
