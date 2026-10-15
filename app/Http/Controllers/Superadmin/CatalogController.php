<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseBatch;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function categories(Request $request)
    {
        $categories = Category::query()
            ->with(['store', 'parent'])
            ->withCount(['children', 'products'])
            ->when($request->store_id, fn ($q, $id) => $q->where('store_id', $id))
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stores = Store::orderBy('name')->get(['id', 'name']);

        return view('superadmin.catalog.categories', compact('categories', 'stores'));
    }

    public function products(Request $request)
    {
        $products = Product::query()
            ->with(['store', 'category'])
            ->when($request->store_id, fn ($q, $id) => $q->where('store_id', $id))
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stores = Store::orderBy('name')->get(['id', 'name']);

        return view('superadmin.catalog.products', compact('products', 'stores'));
    }

    public function batches(Request $request)
    {
        $batches = PurchaseBatch::query()
            ->with(['store', 'creator'])
            ->withCount('items')
            ->when($request->store_id, fn ($q, $id) => $q->where('store_id', $id))
            ->latest('purchase_date')
            ->paginate(20)
            ->withQueryString();

        $stores = Store::orderBy('name')->get(['id', 'name']);

        return view('superadmin.catalog.batches', compact('batches', 'stores'));
    }

    public function stock(Request $request)
    {
        $movements = StockMovement::query()
            ->with(['store', 'product', 'creator'])
            ->when($request->store_id, fn ($q, $id) => $q->where('stock_movements.store_id', $id))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stores = Store::orderBy('name')->get(['id', 'name']);

        return view('superadmin.catalog.stock', compact('movements', 'stores'));
    }

    public function audit(Request $request)
    {
        $logs = AuditLog::query()
            ->with(['user', 'store'])
            ->when($request->store_id, fn ($q, $id) => $q->where('store_id', $id))
            ->when($request->action, fn ($q, $action) => $q->where('action', 'like', "%{$action}%"))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        $stores = Store::orderBy('name')->get(['id', 'name']);

        return view('superadmin.catalog.audit', compact('logs', 'stores'));
    }
}
