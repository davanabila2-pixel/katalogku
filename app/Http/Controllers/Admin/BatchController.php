<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseBatchRequest;
use App\Models\Product;
use App\Models\PurchaseBatch;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\BarcodeService;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request, Store $store)
    {
        $batches = PurchaseBatch::where('store_id', $store->id)
            ->with(['creator'])
            ->withCount('items')
            ->when($request->q, fn ($q, $term) => $q->where(function ($qq) use ($term) {
                $qq->where('batch_code', 'like', "%{$term}%")
                    ->orWhere('supplier_name', 'like', "%{$term}%");
            }))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest('purchase_date')
            ->paginate(15)
            ->withQueryString();

        return view('admin.batches.index', compact('store', 'batches'));
    }

    public function create(Store $store)
    {
        $this->authorize('create', [PurchaseBatch::class, $store->id]);
        $products = Product::forStore($store->id)->orderBy('name')->get(['id', 'name', 'sku']);

        return view('admin.batches.form', compact('store', 'products'));
    }

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
            \Illuminate\Support\Facades\Log::warning('Gagal auto-generate QR batch: '.$e->getMessage());
        }

        return redirect()->route('admin.batches.show', [$store, $batch])
            ->with('status', 'Batch dibuat. Total biaya: Rp '.number_format($batch->totalCost(), 0, ',', '.').$barcodeNotice);
    }

    public function show(Store $store, PurchaseBatch $batch)
    {
        $this->ensureSameStore($store, $batch);
        $this->authorize('view', $batch);
        $batch->load(['items.product', 'creator']);

        return view('admin.batches.show', compact('store', 'batch'));
    }

    public function generateQrCodes(Request $request, Store $store, PurchaseBatch $batch)
    {
        $this->ensureSameStore($store, $batch);
        $this->authorize('view', $batch);

        try {
            $updated = app(BarcodeService::class)->generateBatchQrCodes($batch, $request->user());
            AuditLogger::log('batch.qr_generated', $batch, "Generate QR code untuk produk pada batch {$batch->batch_code}.", ['total_produk' => count($updated)], $request->user(), $store->id);

            return back()->with('status', 'QR code dan label untuk '.count($updated).' produk dalam batch ini berhasil di-generate.');
        } catch (\Throwable $e) {
            return back()->withErrors(['qr' => 'Gagal generate QR code batch: '.$e->getMessage()]);
        }
    }

    public function labels(Request $request, Store $store, PurchaseBatch $batch)
    {
        $this->ensureSameStore($store, $batch);
        $this->authorize('view', $batch);
        $batch->load(['items.product', 'creator']);

        $mode = $request->query('mode', 'qty'); // 'qty' atau 'unique'

        // Pastikan semua produk minimal memiliki barcode string
        foreach ($batch->items as $item) {
            if ($item->product && empty($item->product->barcode)) {
                $item->product->barcode = app(BarcodeService::class)->generateBarcodeString($item->product);
                $item->product->saveQuietly();
            }
        }

        return view('admin.batches.labels', compact('store', 'batch', 'mode'));
    }

    public function cancel(Request $request, Store $store, PurchaseBatch $batch, InventoryService $inventory)
    {
        $this->ensureSameStore($store, $batch);
        $this->authorize('cancel', $batch);

        $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $inventory->cancelBatch($batch, $request->user(), $request->reason);

        return redirect()->route('admin.batches.show', [$store, $batch])->with('status', 'Batch dibatalkan dan stok dikembalikan.');
    }

    protected function ensureSameStore(Store $store, PurchaseBatch $batch): void
    {
        if ((int) $batch->store_id !== (int) $store->id) {
            abort(404);
        }
    }
}
