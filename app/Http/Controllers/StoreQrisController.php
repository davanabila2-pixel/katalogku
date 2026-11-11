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

        $isDownload = $request->boolean('download');
        $wantsPng = $isDownload || strtolower((string) $request->query('format')) === 'png';
        $amountInt = (int) round($amount);

        if ($wantsPng && extension_loaded('gd')) {
            $headers = [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=300',
            ];
            if ($isDownload) {
                $headers['Content-Disposition'] = "attachment; filename=\"QRIS-{$store->slug}-Rp{$amountInt}.png\"";
            }

            return response($qris->renderPng($payload), 200, $headers);
        }

        $headers = [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=300',
        ];
        if ($isDownload) {
            $headers['Content-Disposition'] = "attachment; filename=\"QRIS-{$store->slug}-Rp{$amountInt}.svg\"";
        }

        return response($qris->renderSvg($payload), 200, $headers);
    }
}
