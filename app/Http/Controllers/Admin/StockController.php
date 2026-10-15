<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request, Store $store)
    {
        $products = Product::forStore($store->id)
            ->with(['category'])
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->when($request->filter === 'empty', fn ($q) => $q->where('stock', '<=', 0))
            ->when($request->filter === 'low', fn ($q) => $q->whereBetween('stock', [1, 5]))
            ->orderBy('stock')
            ->paginate(15)
            ->withQueryString();

        return view('admin.stock.index', compact('store', 'products'));
    }

    public function history(Request $request, Store $store)
    {
        $movements = StockMovement::where('store_id', $store->id)
            ->with(['product', 'creator', 'batch'])
            ->when($request->product_id, fn ($q, $id) => $q->where('product_id', $id))
            ->when($request->type, fn ($q, $type) => $q->where('movement_type', $type))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $products = Product::forStore($store->id)->orderBy('name')->get(['id', 'name']);

        return view('admin.stock.history', compact('store', 'movements', 'products'));
    }

    public function activity(Request $request, Store $store)
    {
        $logs = AuditLog::where('store_id', $store->id)
            ->with('user')
            ->latest('created_at')
            ->paginate(20);

        return view('admin.stock.activity', compact('store', 'logs'));
    }
}
