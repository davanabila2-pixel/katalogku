<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /**
     * Daftar stok produk (JSON) untuk tabel stok admin tanpa reload.
     * Filter: q, filter=low|empty.
     */
    public function index(Request $request, Store $store)
    {
        $products = Product::forStore($store->id)
            ->with(['category'])
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->filter === 'empty', fn ($q) => $q->where('stock', '<=', 0))
            ->when($request->filter === 'low', fn ($q) => $q->whereBetween('stock', [1, 5]))
            ->orderBy('stock')
            ->paginate(15);

        return response()->json([
            'data' => $products->getCollection()->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category?->name,
                'stock' => (int) $product->stock,
                'is_bundle' => $product->isBundle(),
                'adjust_url' => route('api.products.adjust', [$store, $product]),
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

    /**
     * Riwayat pergerakan stok (JSON).
     * Filter: product_id, type.
     */
    public function history(Request $request, Store $store)
    {
        $movements = StockMovement::where('store_id', $store->id)
            ->with(['product', 'creator'])
            ->when($request->product_id, fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->type, fn ($q, $type) => $q->where('movement_type', $type))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'data' => $movements->getCollection()->map(fn (StockMovement $movement) => [
                'id' => $movement->id,
                'created_at_formatted' => $movement->created_at?->format('d M Y H:i'),
                'product_name' => $movement->product?->name,
                'product_id' => $movement->product_id,
                'movement_type' => $movement->movement_type,
                'quantity' => (int) $movement->quantity,
                'notes' => $movement->notes,
                'creator_name' => $movement->creator?->name,
            ])->values(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
                'from' => $movements->firstItem(),
                'to' => $movements->lastItem(),
            ],
        ]);
    }

    /**
     * Aktivitas toko / audit log ringkas (JSON).
     */
    public function activity(Store $store)
    {
        $logs = AuditLog::where('store_id', $store->id)
            ->with('user')
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $logs->getCollection()->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'created_at_formatted' => $log->created_at?->format('d M Y H:i'),
                'action' => $log->action,
                'description' => $log->description,
                'user_name' => $log->user?->name,
            ])->values(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
        ]);
    }
}
