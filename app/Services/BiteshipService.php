<?php

namespace App\Services;

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
     *
     * @param array $origin ['latitude' => ..., 'longitude' => ...] atau ['postal_code' => ...]
     * @param array $destination ['latitude' => ..., 'longitude' => ...] atau ['postal_code' => ...]
     * @param array $items Daftar produk / berat
     * @param string|null $couriers Daftar kode kurir dipisah koma (cth: jne,sicepat,jnt)
     */
    public function checkRates(array $origin, array $destination, array $items = [], ?string $couriers = null): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'API Key Biteship belum dikonfigurasi di file .env.',
                'rates' => [],
            ];
        }

        $payload = [
            'couriers' => $couriers ?: 'jne,sicepat,jnt,tiki,pos,anteraja,gojek,grab',
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

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(12)
                ->acceptJson()
                ->asJson()
                ->post("{$this->baseUrl}/rates/couriers", $payload);

            if (! $response->successful()) {
                $errorMsg = $response->json('message') ?? 'Gagal mengambil tarif ongkir dari Biteship.';
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
                $price = (float) ($p['price'] ?? 0);
                $rates[] = [
                    'company' => strtolower($p['company'] ?? 'courier'),
                    'courier_name' => $p['courier_name'] ?? strtoupper($p['company'] ?? ''),
                    'courier_code' => $p['courier_code'] ?? ($p['company'] ?? ''),
                    'service_name' => $p['courier_service_name'] ?? ($p['type'] ?? 'Reguler'),
                    'service_code' => $p['courier_service_code'] ?? ($p['type'] ?? 'reg'),
                    'service_type' => $p['service_type'] ?? 'standard',
                    'price' => $price,
                    'price_formatted' => 'Rp ' . number_format($price, 0, ',', '.'),
                    'duration' => $p['duration'] ?? '-',
                    'description' => $p['description'] ?? '',
                ];
            }

            // Urutkan dari harga termurah
            usort($rates, fn ($a, $b) => $a['price'] <=> $b['price']);

            return [
                'success' => true,
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
}

