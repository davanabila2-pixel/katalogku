<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRateCache;
use App\Models\Store;
use App\Services\BiteshipService;
use App\Support\Couriers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierController extends Controller
{
    /**
     * Tampilkan halaman pengaturan Master Ekspedisi & Cek Ongkir Toko.
     */
    public function index(Store $store)
    {
        $this->authorize('update', $store);

        $allCouriers = Couriers::all();
        $activeCodes = $store->activeCourierCodes();
        $googleMapsApiKey = config('services.google.maps_api_key');

        // Statistik cache ongkir offline untuk titik asal toko ini
        $cacheCount = 0;
        $cacheHits = 0;
        if ($store->postal_code || ($store->latitude && $store->longitude)) {
            $query = ShippingRateCache::query();
            if ($store->postal_code) {
                $query->where('origin_postal', $store->postal_code);
            } elseif ($store->latitude && $store->longitude) {
                $query->where('origin_lat', $store->latitude)->where('origin_lng', $store->longitude);
            }
            $cacheCount = (clone $query)->count();
            $cacheHits = (int) (clone $query)->sum('hit_count');
        }

        return view('admin.couriers.index', compact(
            'store',
            'allCouriers',
            'activeCodes',
            'cacheCount',
            'cacheHits',
            'googleMapsApiKey'
        ));
    }

    /**
     * Simpan pengaturan Master Ekspedisi dan Titik Asal Pengiriman Toko.
     */
    public function update(Request $request, Store $store)
    {
        $this->authorize('update', $store);

        $validated = $request->validate([
            'active_couriers' => ['nullable', 'array'],
            'active_couriers.*' => ['string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $updateData = [
            'active_couriers' => Couriers::sanitize($request->input('active_couriers', [])),
            'address' => $validated['address'] ?? null,
            'province' => $validated['province'] ?? null,
            'city' => $validated['city'] ?? null,
            'district' => $validated['district'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
        ];

        if (array_key_exists('latitude', $validated) && $validated['latitude'] !== null) {
            $updateData['latitude'] = (float) $validated['latitude'];
        }
        if (array_key_exists('longitude', $validated) && $validated['longitude'] !== null) {
            $updateData['longitude'] = (float) $validated['longitude'];
        }

        $store->update($updateData);

        return back()->with('success', 'Pengaturan Master Ekspedisi dan Alamat Asal Pengiriman Toko berhasil diperbarui.');
    }

    /**
     * Uji coba simulasi cek ongkir dari toko ke alamat tujuan langsung dari admin.
     */
    public function simulate(Request $request, Store $store, BiteshipService $biteship): JsonResponse
    {
        $this->authorize('update', $store);

        $origin = $store->originForBiteship();
        if (empty($origin['postal_code']) && (empty($origin['latitude']) || empty($origin['longitude']))) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat asal toko belum memiliki Kode Pos atau Titik Koordinat GPS. Lengkapi formulir di atas dan simpan terlebih dahulu.',
                'rates' => [],
            ], 422);
        }

        $activeCouriers = $store->activeCourierCodes();
        if (empty($activeCouriers)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada ekspedisi yang aktif. Aktifkan minimal satu ekspedisi pada pilihan kurir di atas.',
                'rates' => [],
            ], 422);
        }

        $destinationPostal = trim((string) $request->input('destination_postal'));
        $destinationLat = $request->input('destination_lat');
        $destinationLng = $request->input('destination_lng');

        if (empty($destinationPostal) && (empty($destinationLat) || empty($destinationLng))) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan isi Kode Pos Tujuan atau pilih titik koordinat/kecamatan tujuan untuk simulasi cek ongkir.',
                'rates' => [],
            ], 422);
        }

        $weight = max(100, (int) $request->input('weight_grams', 1000));
        $items = [
            [
                'name' => 'Paket Uji Coba Simulasi',
                'price' => 50000,
                'quantity' => 1,
                'weight' => $weight,
            ],
        ];

        $destination = [
            'postal_code' => $destinationPostal ?: null,
            'latitude' => $destinationLat ?: null,
            'longitude' => $destinationLng ?: null,
            'city' => $request->input('destination_city'),
            'district' => $request->input('destination_district'),
            'province' => $request->input('destination_province'),
        ];

        $result = $biteship->checkRates($origin, $destination, $items, implode(',', $activeCouriers));

        return response()->json($result);
    }

    /**
     * Bersihkan cache tarif ongkir yang tersimpan di DB untuk toko ini.
     */
    public function clearCache(Store $store)
    {
        $this->authorize('update', $store);

        $deleted = 0;
        if ($store->postal_code) {
            $deleted += ShippingRateCache::where('origin_postal', $store->postal_code)->delete();
        }
        if ($store->latitude && $store->longitude) {
            $deleted += ShippingRateCache::where('origin_lat', $store->latitude)
                ->where('origin_lng', $store->longitude)
                ->delete();
        }

        return back()->with('success', "Cache tarif ongkir ({$deleted} rute) berhasil dibersihkan. Sistem akan memperbarui tarif baru otomatis saat cek ongkir berikutnya.");
    }
}

