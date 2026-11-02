<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreLink;
use App\Models\StoreSlugHistory;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function home()
    {
        // Halaman / kini landing page project, bukan katalog gabungan, jadi tidak ada query data toko.
        return view('public.home', [
            'seo' => [
                'title' => 'Katalog: buat katalog toko Anda dalam satu tautan',
                'description' => 'Masukkan barang, harga, dan stok toko ke Katalog. Pelanggan tinggal membuka tautannya, tanpa instal aplikasi dan tanpa membuat akun.',
            ],
        ]);
    }

    public function stores(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $stores = Store::active()
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('public.stores', compact('stores'));
    }

    protected function findStore(string $slug): Store|\Illuminate\Http\RedirectResponse
    {
        $store = Store::where('slug', $slug)->first();

        if (! $store) {
            $history = StoreSlugHistory::where('old_slug', $slug)->with('store')->first();
            if ($history && $history->store) {
                return redirect($history->store->url(), 301);
            }
            abort(404);
        }

        if (! $store->isActive()) {
            abort(404);
        }

        return $store;
    }

    public function storeShow(Request $request, string $slug)
    {
        $found = $this->findStore($slug);
        if ($found instanceof \Illuminate\Http\RedirectResponse) {
            return $found;
        }

        return $this->catalog($request, $found, null);
    }

    public function categoryShow(Request $request, string $storeSlug, string $categorySlug, ?string $subSlug = null)
    {
        $found = $this->findStore($storeSlug);
        if ($found instanceof \Illuminate\Http\RedirectResponse) {
            return $found;
        }
        $store = $found;

        $category = Category::forStore($store->id)
            ->where('slug', $subSlug ?? $categorySlug)
            ->firstOrFail();

        if (! $category->is_active) {
            abort(404);
        }

        if ($subSlug && (int) $category->parent_id !== (int) Category::forStore($store->id)->where('slug', $categorySlug)->value('id')) {
            abort(404);
        }

        return $this->catalog($request, $store, $category);
    }

    protected function catalog(Request $request, Store $store, ?Category $category)
    {
        $rootCategories = Category::forStore($store->id)
            ->active()
            ->roots()
            ->with(['children' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $activeCategory = $category;
        $categoryIds = null;
        if ($category) {
            $categoryIds = array_merge([$category->id], $category->descendantIds());
        }

        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $products = Product::forStore($store->id)
            ->publiclyVisible()
            ->when($store->hide_component_products, fn ($q) => $q->where(function ($qq) {
                $qq->where('is_bundle', true)
                    ->orWhereDoesntHave('partOfBundles', fn ($b) => $b->where('status', 'active'));
            }))
            ->with(['category', 'store', 'bundleItems'])
            ->when($categoryIds, fn ($q) => $q->whereIn('category_id', $categoryIds))
            ->when($request->q, fn ($q, $term) => $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            }))
            ->when($request->min_price, fn ($q, $min) => $q->where('selling_price', '>=', $min))
            ->when($request->max_price, fn ($q, $max) => $q->where('selling_price', '<=', $max))
            ->when($request->in_stock, fn ($q) => $q->inStock())
            ->when($request->sort === 'price_asc', fn ($q) => $q->orderBy('selling_price'))
            ->when($request->sort === 'price_desc', fn ($q) => $q->orderByDesc('selling_price'))
            ->when($request->sort === 'name', fn ($q) => $q->orderBy('name'))
            ->when(! in_array($request->sort, ['price_asc', 'price_desc', 'name']), fn ($q) => $q->latest())
            ->paginate($perPage)
            ->withQueryString();

        $seo = [
            'title' => $category ? "{$category->name} — {$store->name}" : $store->name,
            'description' => $category?->description ?? $store->description ?? "Katalog {$store->name}.",
            'canonical' => $category
                ? url("/{$store->slug}/kategori/{$category->slug}")
                : $store->url(),
            'image' => $store->logoUrl(),
        ];

        $soldOrders = Order::where('store_id', $store->id)
            ->where('status', 'completed')
            ->with(['product', 'items.product'])
            ->latest('reviewed_at')
            ->latest()
            ->get();

        return view('public.store', compact('store', 'rootCategories', 'activeCategory', 'products', 'seo', 'soldOrders'));
    }

    public function storeSold(Request $request, string $storeSlug)
    {
        $found = $this->findStore($storeSlug);
        if ($found instanceof \Illuminate\Http\RedirectResponse) {
            return $found;
        }
        $store = $found;

        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $soldOrders = Order::where('store_id', $store->id)
            ->where('status', 'completed')
            ->with(['product', 'items.product'])
            ->latest('reviewed_at')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $seo = [
            'title' => "Produk Terjual & Ulasan — {$store->name}",
            'description' => "Daftar produk yang telah berhasil terjual beserta ulasan asli dari pembeli di {$store->name}.",
            'canonical' => route('store.sold', $store->slug),
            'image' => $store->bannerUrl() ?? $store->logoUrl(),
        ];

        return view('public.store-sold', compact('store', 'soldOrders', 'seo'));
    }

    public function storeLinks(string $storeSlug)
    {
        $found = $this->findStore($storeSlug);
        if ($found instanceof \Illuminate\Http\RedirectResponse) {
            return $found;
        }
        $store = $found;

        $links = StoreLink::forStore($store->id)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $seo = [
            'title' => "Tautan: {$store->name}",
            'description' => $store->slogan ?? "Kumpulan tautan resmi {$store->name} dalam satu halaman.",
            'canonical' => route('store.links', $store->slug),
            'image' => $store->logoUrl(),
        ];

        $hideAppbarFooter = true;

        return view('public.links', compact('store', 'links', 'seo', 'hideAppbarFooter'));
    }

    public function productShow(string $storeSlug, string $productSlug)
    {
        $found = $this->findStore($storeSlug);
        if ($found instanceof \Illuminate\Http\RedirectResponse) {
            return $found;
        }
        $store = $found;

        $product = Product::forStore($store->id)
            ->where('slug', $productSlug)
            ->where('status', 'active')
            ->with([
                'category',
                'store',
                'bundleItems' => fn ($q) => $q->where('status', 'active'),
                'partOfBundles' => fn ($q) => $q->where('status', 'active'),
            ])
            ->firstOrFail();

        $related = Product::forStore($store->id)
            ->publiclyVisible()
            ->when($store->hide_component_products, fn ($q) => $q->where(function ($qq) {
                $qq->where('is_bundle', true)
                    ->orWhereDoesntHave('partOfBundles', fn ($b) => $b->where('status', 'active'));
            }))
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->take(4)
            ->get();

        $seo = [
            'title' => "{$product->name} — {$store->name}",
            'description' => mb_substr($product->description ?? $product->name, 0, 160),
            'canonical' => url("/{$store->slug}/produk/{$product->slug}"),
            'image' => $product->imageUrl(),
        ];

        $rootCategories = Category::forStore($store->id)
            ->active()
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(5)
            ->get();

        return view('public.product', compact('store', 'product', 'related', 'seo', 'rootCategories'));
    }

    public function sitemap()
    {
        $stores = Store::active()->get();
        $products = Product::where('status', 'active')
            ->whereHas('store', fn ($q) => $q->where('status', 'active'))
            ->with('store')
            ->get();

        return response()->view('public.sitemap', compact('stores', 'products'))
            ->header('Content-Type', 'application/xml');
    }
}
