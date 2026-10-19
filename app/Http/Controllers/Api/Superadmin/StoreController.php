<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /**
     * Daftar seluruh toko (JSON) untuk superadmin tanpa reload.
     * Filter: q, status.
     */
    public function index(Request $request)
    {
        $stores = Store::query()
            ->withCount(['products', 'categories', 'purchaseBatches'])
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $stores->getCollection()->map(fn (Store $store) => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'url' => $store->url(),
                'products_count' => (int) $store->products_count,
                'categories_count' => (int) $store->categories_count,
                'purchase_batches_count' => (int) $store->purchase_batches_count,
                'status' => $store->status,
                'show_url' => route('superadmin.stores.show', $store),
            ])->values(),
            'meta' => [
                'current_page' => $stores->currentPage(),
                'last_page' => $stores->lastPage(),
                'per_page' => $stores->perPage(),
                'total' => $stores->total(),
                'from' => $stores->firstItem(),
                'to' => $stores->lastItem(),
            ],
        ]);
    }
}
