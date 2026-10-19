<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Daftar produk (JSON) untuk tabel admin tanpa reload halaman.
     * Filter yang didukung sama seperti halaman web sebelumnya:
     * q, category_id, status, visibility, dan stock.
     */
    public function index(Request $request, Store $store)
    {
        $products = Product::forStore($store->id)
            ->with('category')
            ->when($request->q, fn ($q, $term) => $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%");
            }))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->visibility === 'visible', fn ($q) => $q->where('is_hidden_from_catalog', false))
            ->when($request->visibility === 'hidden', fn ($q) => $q->where('is_hidden_from_catalog', true))
            ->when($request->stock === 'empty', fn ($q) => $q->where('stock', '<=', 0))
            ->when($request->stock === 'low', fn ($q) => $q->whereBetween('stock', [1, 5]))
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $products->getCollection()->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'slug' => $product->slug,
                'barcode' => $product->barcode,
                'barcode_url' => $product->barcodeUrl(),
                'scan_url' => $product->scanUrl(),
                'category' => $product->category?->name,
                'selling_price' => (float) $product->selling_price,
                'selling_price_formatted' => 'Rp '.number_format((float) $product->selling_price, 0, ',', '.'),
                'stock' => (int) $product->stock,
                'status' => $product->status,
                'is_bundle' => $product->isBundle(),
                'is_hidden' => $product->isHiddenFromCatalog(),
                'edit_url' => route('admin.products.edit', [$store, $product]),
                'destroy_url' => route('admin.products.destroy', [$store, $product]),
            ])->values(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ],
        ]);
    }
}
