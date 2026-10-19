<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseBatchRequest;
use App\Models\Product;
use App\Models\PurchaseBatch;
use App\Models\Store;
use App\Services\BarcodeService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    /**
     * Pilihan produk untuk baris item di modal "Buat Batch".
     * Sama dengan form web: semua produk toko (termasuk paket setelan).
     */
    public function options(Request $request, Store $store)
    {
        $products = Product::forStore($store->id)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock']);

        return response()->json([
            'data' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => (int) $product->stock,
            ])->values(),
            'suggested_code' => 'BATCH-'.now()->format('Ymd-His'),
            'today' => now()->toDateString(),
        ]);
    }

    /**
     * Buat batch pembelian tanpa reload halaman.
     * Alur sama dengan versi web: stok masuk lewat InventoryService,
     * QR code batch dibuat best-effort, lalu UI diarahkan ke halaman
     * detail batch (untuk cetak label).
     */
    public function store(PurchaseBatchRequest $request, Store $store, InventoryService $inventory)
    {
        $data = $request->validated();

        $batch = $inventory->createBatch(
            $data + ['store_id' => $store->id],
            $data['items'],
            $request->user()
        );

        $barcodeNotice = '';
        try {
            $updated = app(BarcodeService::class)->generateBatchQrCodes($batch, $request->user());
            if (count($updated) > 0) {
                $barcodeNotice = ' QR code identitas produk batch berhasil di-generate.';
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal auto-generate QR batch (API): '.$e->getMessage());
        }

        return response()->json([
            'message' => 'Batch dibuat. Total biaya: Rp '.number_format($batch->totalCost(), 0, ',', '.').$barcodeNotice,
            'batch_id' => $batch->id,
            'redirect_url' => route('admin.batches.show', [$store, $batch]),
        ], 201);
    }
}
