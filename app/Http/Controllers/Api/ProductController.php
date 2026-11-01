<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\StockAdjustRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\AuditLogger;
use App\Services\BarcodeService;
use App\Services\HppService;
use App\Services\InventoryService;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $adminProdPrefix = route('admin.products.index', $store);
        $apiProdPrefix = route('api.products.index', $store);

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
                'edit_url' => "{$adminProdPrefix}/{$product->id}/edit",
                'update_url' => "{$apiProdPrefix}/{$product->id}",
                'form_data_url' => "{$apiProdPrefix}/{$product->id}/form-data",
                'destroy_url' => "{$apiProdPrefix}/{$product->id}",
                'adjust_url' => "{$apiProdPrefix}/{$product->id}/adjust",
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
     * Data untuk modal "Tambah produk": pilihan kategori (aktif) + produk
     * satuan yang bisa dijadikan komponen paket setelan.
     */
    public function formData(Request $request, Store $store)
    {
        $this->authorize('create', [Product::class, $store->id]);

        return response()->json([
            'categories' => $this->categoryOptions($store, true),
            'components' => $this->componentOptions($store),
        ]);
    }

    /**
     * Data untuk modal "Edit produk": nilai produk + komponen paket +
     * HPP/margin + info barcode. Mengikuti form web edit: kategori semua.
     */
    public function productFormData(Request $request, Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);
        $this->authorize('update', $product);

        $product->loadMissing('bundleItems');

        return response()->json([
            'categories' => $this->categoryOptions($store, false),
            'components' => $this->componentOptions($store, $product->id),
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'category_id' => $product->category_id,
                'description' => $product->description,
                'selling_price' => (float) $product->selling_price,
                'status' => $product->status,
                'is_bundle' => $product->isBundle(),
                'is_hidden_from_catalog' => (bool) $product->is_hidden_from_catalog,
                'stock' => (int) $product->stock,
                'hpp' => app(HppService::class)->hppForProduct($product),
                'margin' => app(HppService::class)->marginForProduct($product),
                'update_url' => route('api.products.update', [$store, $product]),
            ],
            'bundle_items' => $product->bundleItems->map(fn (Product $item) => [
                'product_id' => $item->id,
                'quantity' => (int) $item->pivot->quantity,
            ])->values(),
            'barcode' => [
                'code' => $product->barcode,
                'label_url' => $product->barcodeUrl(),
                'scan_url' => $product->scanUrl(),
                'regenerate_url' => route('api.products.barcode', [$store, $product]),
            ],
        ]);
    }

    /**
     * Simpan produk baru tanpa reload halaman (termasuk paket setelan).
     * Aturan sama dengan versi web: slug otomatis, komponen paket disinkronkan,
     * barcode dibuat best-effort (kegagalan Drive tidak membatalkan produk).
     */
    public function store(ProductRequest $request, Store $store)
    {
        $data = $request->validated();
        $data['store_id'] = $store->id;
        $data['slug'] = $data['slug'] ?? SlugService::uniqueProductSlug($store->id, $data['name']);
        $data['is_bundle'] = (bool) ($data['is_bundle'] ?? false);
        $data['is_hidden_from_catalog'] = $data['is_bundle'] ? false : (bool) ($data['is_hidden_from_catalog'] ?? false);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($store, $request->file('image'));
        }
        $bundleItemsData = $data['bundle_items'] ?? null;
        unset($data['image'], $data['bundle_items']);

        $product = Product::create($data + ['store_id' => $store->id]);

        if ($product->isBundle() && ! empty($bundleItemsData)) {
            $product->bundleItems()->sync($this->bundleSyncData($bundleItemsData));
            $product->syncBundleStock();
        }

        $barcodeNotice = '';
        try {
            app(BarcodeService::class)->generateAndUpload($product, $request->user());
            $barcodeNotice = 'Barcode identitas tersimpan di Google Drive.';
        } catch (\Throwable $e) {
            Log::warning('Gagal generate barcode saat buat produk (API): '.$e->getMessage(), ['product_id' => $product->id]);
            $barcodeNotice = 'Catatan barcode: '.$e->getMessage();
        }

        AuditLogger::log('product.created', $product, "Produk {$product->name} dibuat.", null, $request->user(), $store->id);

        return response()->json([
            'message' => 'Produk dibuat.',
            'barcode_notice' => $barcodeNotice,
            'product_id' => $product->id,
        ], 201);
    }

    /**
     * Perbarui produk tanpa reload halaman (termasuk paket setelan).
     */
    public function update(ProductRequest $request, Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);
        $data = $request->validated();
        $data['is_bundle'] = (bool) ($data['is_bundle'] ?? false);
        $data['is_hidden_from_catalog'] = $data['is_bundle'] ? false : (bool) ($data['is_hidden_from_catalog'] ?? false);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($store, $request->file('image'), $product->image_path);
        }
        $bundleItemsData = $data['bundle_items'] ?? null;
        unset($data['image'], $data['store_id'], $data['bundle_items']);

        $product->update($data);

        if ($product->isBundle()) {
            $product->bundleItems()->sync($this->bundleSyncData($bundleItemsData ?? []));
            $product->syncBundleStock();
        } else {
            $product->bundleItems()->detach();
        }

        foreach ($product->partOfBundles as $bundle) {
            $bundle->syncBundleStock();
        }

        AuditLogger::log('product.updated', $product, "Produk {$product->name} diperbarui.", null, $request->user(), $store->id);

        return response()->json([
            'message' => 'Produk diperbarui.',
            'product_id' => $product->id,
        ]);
    }

    /**
     * Generate ulang barcode & simpan ke Google Drive tanpa reload halaman.
     */
    public function regenerateBarcode(Request $request, Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);
        $this->authorize('update', $product);

        try {
            app(BarcodeService::class)->generateAndUpload($product, $request->user());
            AuditLogger::log('product.barcode_regenerated', $product, "Barcode identitas produk {$product->name} di-generate ulang.", null, $request->user(), $store->id);

            $product->refresh();

            return response()->json([
                'message' => 'Barcode dan label produk berhasil di-generate dan disimpan ke Google Drive.',
                'barcode' => [
                    'code' => $product->barcode,
                    'label_url' => $product->barcodeUrl(),
                    'scan_url' => $product->scanUrl(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal mengunggah barcode ke Google Drive: '.$e->getMessage(),
            ], 422);
        }
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

    /**
     * Pilihan kategori untuk form modal. $activeOnly meniru form web create.
     */
    protected function categoryOptions(Store $store, bool $activeOnly)
    {
        return Category::forStore($store->id)
            ->when($activeOnly, fn ($q) => $q->active())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->values();
    }

    /**
     * Produk satuan yang bisa dipilih sebagai komponen paket setelan.
     */
    protected function componentOptions(Store $store, ?int $excludeId = null)
    {
        return Product::forStore($store->id)
            ->where('is_bundle', false)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'selling_price', 'stock'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'selling_price' => (float) $product->selling_price,
                'stock' => (int) $product->stock,
            ])->values();
    }

    /**
     * Bentuk data sync komponen paket: [product_id => ['quantity' => n]].
     */
    protected function bundleSyncData(array $items): array
    {
        $syncData = [];
        foreach ($items as $item) {
            if (! empty($item['product_id'])) {
                $syncData[$item['product_id']] = ['quantity' => max(1, (int) ($item['quantity'] ?? 1))];
            }
        }

        return $syncData;
    }
}
