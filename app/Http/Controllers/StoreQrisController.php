<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\QrisService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StoreQrisController extends Controller
{
    /**
     * Gambar QRIS dinamis (SVG) yang membawa nominal pembayaran, sehingga saat discan
     * aplikasi bank / e-wallet langsung menampilkan total yang harus dibayar.
     */
    public function show(Request $request, string $storeSlug, QrisService $qris): Response
    {
        $store = Store::where('slug', $storeSlug)->active()->firstOrFail();

        $amount = (float) $request->query('amount', 0);
        abort_if($amount < 1 || $amount > 100000000 || ! $store->hasDynamicQris(), 404);

        $payload = $qris->makeDynamic($store->qris_payload, $amount);
        abort_if($payload === null, 404);

        return response($qris->renderSvg($payload), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
