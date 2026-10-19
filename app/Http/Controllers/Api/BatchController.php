<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseBatch;
use App\Models\Store;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    /**
     * Daftar batch pembelian (JSON) untuk tabel admin tanpa reload.
     * Filter: q (kode/supplier), status. Urutan sama dengan versi web.
     */
    public function index(Request $request, Store $store)
    {
        $batches = PurchaseBatch::where('store_id', $store->id)
            ->withCount('items')
            ->when($request->q, fn ($q, $term) => $q->where(function ($qq) use ($term) {
                $qq->where('batch_code', 'like', "%{$term}%")
                    ->orWhere('supplier_name', 'like', "%{$term}%");
            }))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('purchase_date')
            ->paginate(15);

        return response()->json([
            'data' => $batches->getCollection()->map(fn (PurchaseBatch $batch) => [
                'id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'purchase_date_formatted' => $batch->purchase_date?->format('d M Y'),
                'supplier_name' => $batch->supplier_name,
                'items_count' => (int) $batch->items_count,
                'status' => $batch->status,
                'show_url' => route('admin.batches.show', [$store, $batch]),
            ])->values(),
            'meta' => [
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
                'from' => $batches->firstItem(),
                'to' => $batches->lastItem(),
            ],
        ]);
    }
}
