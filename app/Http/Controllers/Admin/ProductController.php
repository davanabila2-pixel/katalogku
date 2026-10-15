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
use App\Services\HppService;
use App\Services\InventoryService;
use App\Services\SlugService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use HandlesImages;

    public function index(Request $request, Store $store)
    {
        $products = Product::forStore($store->id)
            ->with(['category'])
            ->when($request->q, fn ($q, $term) => $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%");
            }))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->stock === 'empty', fn ($q) => $q->where('stock', '<=', 0))
            ->when($request->stock === 'low', fn ($q) => $q->whereBetween('stock', [1, 5]))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = Category::forStore($store->id)->orderBy('name')->get();

        return view('admin.products.index', compact('store', 'products', 'categories'));
    }

    public function create(Store $store)
    {
        $this->authorize('create', [Product::class, $store->id]);
        $categories = Category::forStore($store->id)->active()->orderBy('name')->get();

        return view('admin.products.form', compact('store', 'categories') + ['product' => new Product(['store_id' => $store->id])]);
    }

    public function store(ProductRequest $request, Store $store)
    {
        $data = $request->validated();
        $data['store_id'] = $store->id;
        $data['slug'] = $data['slug'] ?? SlugService::uniqueProductSlug($store->id, $data['name']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($store, $request->file('image'));
        }
        unset($data['image']);

        $product = Product::create($data + ['store_id' => $store->id]);
        AuditLogger::log('product.created', $product, "Produk {$product->name} dibuat.", null, $request->user(), $store->id);

        return redirect()->route('admin.products.index', $store)->with('status', 'Produk dibuat.');
    }

    public function edit(Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);
        $this->authorize('update', $product);
        $categories = Category::forStore($store->id)->orderBy('name')->get();
        $hpp = app(HppService::class)->hppForProduct($product);
        $margin = app(HppService::class)->marginForProduct($product);

        return view('admin.products.form', compact('store', 'product', 'categories', 'hpp', 'margin'));
    }

    public function update(ProductRequest $request, Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($store, $request->file('image'), $product->image_path);
        }
        unset($data['image'], $data['store_id']);

        $product->update($data);
        AuditLogger::log('product.updated', $product, "Produk {$product->name} diperbarui.", null, $request->user(), $store->id);

        return redirect()->route('admin.products.index', $store)->with('status', 'Produk diperbarui.');
    }

    public function destroy(Request $request, Store $store, Product $product)
    {
        $this->ensureSameStore($store, $product);
        $this->authorize('delete', $product);

        if ($product->stockMovements()->exists() || $product->batchItems()->exists()) {
            $product->update(['status' => 'inactive']);
            AuditLogger::log('product.archived', $product, "Produk {$product->name} diarsipkan (memiliki riwayat stok).", null, $request->user(), $store->id);

            return redirect()->route('admin.products.index', $store)->with('status', 'Produk memiliki riwayat stok — diarsipkan, bukan dihapus.');
        }

        $name = $product->name;
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

    protected function ensureSameStore(Store $store, Product $product): void
    {
        if ((int) $product->store_id !== (int) $store->id) {
            abort(404);
        }
    }
}
