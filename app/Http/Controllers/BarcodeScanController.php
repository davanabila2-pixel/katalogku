<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class BarcodeScanController extends Controller
{
    public function scan(Request $request, string $code)
    {
        $product = Product::where('barcode', $code)
            ->orWhere('sku', $code)
            ->orWhere('id', is_numeric($code) ? (int) $code : 0)
            ->firstOrFail();

        $store = $product->store;

        // Jika admin/superadmin sedang login dan memiliki hak akses toko ini:
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->managesStore($store->id)) {
                return redirect()->route('admin.products.edit', [$store, $product]);
            }
        }

        // Jika ingin mengelola sebagai admin toko (diarahkan ke login dengan redirect langsung ke produk)
        if ($request->boolean('admin')) {
            session(['url.intended' => route('admin.products.edit', [$store, $product])]);

            return redirect()->route('login')->with('status', 'Silakan masuk untuk mengelola produk ini.');
        }

        // Default publik: Arahkan langsung ke halaman detail produk katalog toko
        return redirect()->route('store.product', [$store->slug, $product->slug]);
    }
}
