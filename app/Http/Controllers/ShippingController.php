<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\Store;
use App\Services\BiteshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    /**
     * Hitung ongkos kirim real-time menggunakan Biteship dari toko ke alamat pembeli.
     */
    public function rates(Request $request, string $storeSlug, BiteshipService $biteship): JsonResponse
    {
        $store = Store::where('slug', $storeSlug)->active()->firstOrFail();

        $destinationLat = $request->input('latitude');
        $destinationLng = $request->input('longitude');
        $destinationPostal = $request->input('postal_code');

        if (empty($destinationPostal) && (empty($destinationLat) || empty($destinationLng))) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan lengkapi kode pos atau koordinat lokasi pengiriman pembeli untuk cek ongkir.',
                'rates' => [],
            ], 422);
        }

        // Siapkan origin toko
        $origin = $store->originForBiteship();
        if (empty($origin['postal_code']) && (empty($origin['latitude']) || empty($origin['longitude']))) {
            return response()->json([
                'success' => false,
                'message' => "Toko {$store->name} belum mengatur titik alamat asal / kode pos toko di pengaturan profil toko.",
                'rates' => [],
            ], 422);
        }

        // Siapkan destination pembeli
        $destination = [
            'latitude' => $destinationLat,
            'longitude' => $destinationLng,
            'postal_code' => $destinationPostal,
        ];

        // Hitung estimasi berat dari produk
        $items = [];
        if ($request->filled('product_id')) {
            $product = Product::where('store_id', $store->id)
                ->where('id', $request->input('product_id'))
                ->first();

            if ($product) {
                $qty = max(1, (int) $request->input('quantity', 1));
                $items[] = [
                    'name' => $product->name,
                    'price' => (float) $product->selling_price,
                    'quantity' => $qty,
                    'weight' => 500 * $qty, // 500 gram per produk
                ];
            }
        } else {
            $userId = auth()->id();
            $sessionId = $request->session()->getId();
            $cartItems = CartItem::with('product')
                ->where('store_id', $store->id)
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->when(! $userId, fn ($q) => $q->where('session_id', $sessionId))
                ->get();

            foreach ($cartItems as $it) {
                if ($it->product) {
                    $items[] = [
                        'name' => $it->product->name,
                        'price' => (float) $it->product->selling_price,
                        'quantity' => $it->quantity,
                        'weight' => 500 * $it->quantity,
                    ];
                }
            }
        }

        // Master ekspedisi: hanya ekspedisi yang diaktifkan admin toko yang ditawarkan.
        $activeCouriers = $store->activeCourierCodes();
        if (empty($activeCouriers)) {
            return response()->json([
                'success' => true,
                'message' => "Toko {$store->name} belum mengaktifkan ekspedisi. Silakan pilih Ambil Sendiri / koordinasi ongkir via WhatsApp.",
                'rates' => [],
            ]);
        }

        $result = $biteship->checkRates($origin, $destination, $items, implode(',', $activeCouriers));

        if (! empty($result['rates'])) {
            $result['rates'] = array_values(array_filter(
                $result['rates'],
                fn ($rate) => in_array(strtolower($rate['company'] ?? ''), $activeCouriers, true)
                    || in_array(strtolower($rate['courier_code'] ?? ''), $activeCouriers, true)
            ));
        }

        return response()->json($result);
    }
}

