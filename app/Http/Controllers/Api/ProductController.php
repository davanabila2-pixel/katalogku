<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustRequest;
use App\Models\Product;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use HandlesImages;

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
                'destroy_url' => route('api.products.destroy', [$store, $product]),
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
     * Hapus/arsipkan produk tanpa reload halaman.
     * Aturan sama dengan destroy versi web: produk yang menjadi komponen
     * paket setelan ditolak, produk dengan riwayat stok hanya diarsipkan.
     */
    public function destroy(Request $request, Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);
        $this->authorize('delete', $product);

        if ($product->partOfBundles()->exists()) {
            $bundleNames = $product->partOfBundles()->pluck('name')->implode(', ');

            return response()->json([
                'message' => "Produk ini tidak dapat dihapus karena menjadi komponen dalam paket setelan: {$bundleNames}. Hapus komponen dari paket terlebih dahulu agar stok tidak rancu.",
            ], 422);
        }

        if ($product->stockMovements()->exists() || $product->batchItems()->exists()) {
            $product->update(['status' => 'inactive']);
            foreach ($product->partOfBundles as $bundle) {
                $bundle->syncBundleStock();
            }
            AuditLogger::log('product.archived', $product, "Produk {$product->name} diarsipkan (memiliki riwayat stok).", null, $request->user(), $store->id);

            return response()->json([
                'message' => 'Produk memiliki riwayat stok — diarsipkan, bukan dihapus.',
                'action' => 'archived',
            ]);
        }

        $name = $product->name;
        if ($product->image_path) {
            $this->deleteImage($store, $product->image_path);
        }
        $product->delete();
        AuditLogger::log('product.deleted', null, "Produk {$name} dihapus.", ['store_id' => $store->id], $request->user(), $store->id);

        return response()->json([
            'message' => 'Produk dihapus.',
            'action' => 'deleted',
        ]);
    }

    /**
     * Koreksi stok cepat (selisih) tanpa reload halaman.
     * Validasi & aturan bisnis sama dengan koreksi stok versi web
     * (bundle tidak bisa dikoreksi manual, alasan wajib untuk audit).
     */
    public function adjust(StockAdjustRequest $request, Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);

        app(InventoryService::class)->adjustStock(
            $product, (int) $request->quantity, $request->user(), $request->reason
        );

        return response()->json([
            'message' => 'Stok dikoreksi.',
            'product_id' => $product->id,
            'stock' => (int) $product->fresh()->stock,
        ]);
    }

    protected function ensureSameStore(Store $store, Product $product): void
    {
        if ((int) $product->store_id !== (int) $store->id) {
            abort(404);
        }
    }
}
