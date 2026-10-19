<?php

namespace App\Http\Controllers\Admin;

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

class ProductController extends Controller
{
    use HandlesImages;

    public function index(Request $request, Store $store)
    {
        // Data produk dimuat oleh JavaScript lewat API JSON
        // (lihat App\Http\Controllers\Api\ProductController),
        // Blade hanya menyiapkan kerangka + filter kategori.
        $categories = Category::forStore($store->id)->orderBy('name')->get();

        return view('admin.products.index', compact('store', 'categories'));
    }

    public function create(Store $store)
    {
        $this->authorize('create', [Product::class, $store->id]);
        $categories = Category::forStore($store->id)->active()->orderBy('name')->get();
        $storeProducts = Product::forStore($store->id)->where('is_bundle', false)->orderBy('name')->get();

        return view('admin.products.form', compact('store', 'categories', 'storeProducts') + ['product' => new Product(['store_id' => $store->id])]);
    }

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
            $syncData = [];
            foreach ($bundleItemsData as $item) {
                if (! empty($item['product_id'])) {
                    $syncData[$item['product_id']] = ['quantity' => max(1, (int) ($item['quantity'] ?? 1))];
                }
            }
            $product->bundleItems()->sync($syncData);
            $product->syncBundleStock();
        }

        $barcodeNotice = '';
        try {
            app(BarcodeService::class)->generateAndUpload($product, $request->user());
            $barcodeNotice = ' Barcode identitas tersimpan di Google Drive.';
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal generate barcode saat buat produk: '.$e->getMessage(), ['product_id' => $product->id]);
            $barcodeNotice = ' (Catatan barcode: '.$e->getMessage().')';
        }

        AuditLogger::log('product.created', $product, "Produk {$product->name} dibuat.", null, $request->user(), $store->id);

        return redirect()->route('admin.products.index', $store)->with('status', 'Produk dibuat.'.$barcodeNotice);
    }

    public function edit(Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);
        $this->authorize('update', $product);
        $categories = Category::forStore($store->id)->orderBy('name')->get();
        $storeProducts = Product::forStore($store->id)
            ->where('is_bundle', false)
            ->where('id', '!=', $product->id)
            ->orderBy('name')
            ->get();
        $product->loadMissing('bundleItems');
        $hpp = app(HppService::class)->hppForProduct($product);
        $margin = app(HppService::class)->marginForProduct($product);

        return view('admin.products.form', compact('store', 'product', 'categories', 'storeProducts', 'hpp', 'margin'));
    }

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
            $syncData = [];
            foreach ($bundleItemsData ?? [] as $item) {
                if (! empty($item['product_id'])) {
                    $syncData[$item['product_id']] = ['quantity' => max(1, (int) ($item['quantity'] ?? 1))];
                }
            }
            $product->bundleItems()->sync($syncData);
            $product->syncBundleStock();
        } else {
            $product->bundleItems()->detach();
        }

        foreach ($product->partOfBundles as $bundle) {
            $bundle->syncBundleStock();
        }

        AuditLogger::log('product.updated', $product, "Produk {$product->name} diperbarui.", null, $request->user(), $store->id);

        return redirect()->route('admin.products.index', $store)->with('status', 'Produk diperbarui.');
    }

    public function destroy(Request $request, Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);
        $this->authorize('delete', $product);

        if ($product->partOfBundles()->exists()) {
            $bundleNames = $product->partOfBundles()->pluck('name')->implode(', ');

            return back()->withErrors(['product' => "Produk ini tidak dapat dihapus karena menjadi komponen dalam paket setelan: {$bundleNames}. Hapus komponen dari paket terlebih dahulu agar stok tidak rancu."]);
        }

        if ($product->stockMovements()->exists() || $product->batchItems()->exists()) {
            $product->update(['status' => 'inactive']);
            foreach ($product->partOfBundles as $bundle) {
                $bundle->syncBundleStock();
            }
            AuditLogger::log('product.archived', $product, "Produk {$product->name} diarsipkan (memiliki riwayat stok).", null, $request->user(), $store->id);

            return redirect()->route('admin.products.index', $store)->with('status', 'Produk memiliki riwayat stok — diarsipkan, bukan dihapus.');
        }

        $name = $product->name;
        if ($product->image_path) {
            $this->deleteImage($store, $product->image_path);
        }
        $product->delete();
        AuditLogger::log('product.deleted', null, "Produk {$name} dihapus.", ['store_id' => $store->id], $request->user(), $store->id);

        return redirect()->route('admin.products.index', $store)->with('status', 'Produk dihapus.');
    }

    public function adjust(StockAdjustRequest $request, Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);

        app(InventoryService::class)->adjustStock(
            $product, (int) $request->quantity, $request->user(), $request->reason
        );

        return back()->with('status', 'Stok dikoreksi.');
    }

    public function regenerateBarcode(Request $request, Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);
        $this->authorize('update', $product);

        try {
            app(BarcodeService::class)->generateAndUpload($product, $request->user());
            AuditLogger::log('product.barcode_regenerated', $product, "Barcode identitas produk {$product->name} di-generate ulang.", null, $request->user(), $store->id);

            return back()->with('status', 'Barcode dan label produk berhasil di-generate dan disimpan ke Google Drive.');
        } catch (\Throwable $e) {
            return back()->withErrors(['barcode' => 'Gagal mengunggah barcode ke Google Drive: '.$e->getMessage()]);
        }
    }

    protected function ensureSameStore(Store $store, Product $product): void
    {
        if ((int) $product->store_id !== (int) $store->id) {
            abort(404);
        }
    }
}
