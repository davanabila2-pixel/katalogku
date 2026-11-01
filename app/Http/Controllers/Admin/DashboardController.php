<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PurchaseBatch;
use App\Models\Store;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(Store $store)
    {
        // Gabungkan agregat produk & stok dalam 1 query
        $productStats = $store->products()
            ->selectRaw('COUNT(*) as total_products, COALESCE(SUM(stock), 0) as total_stock')
            ->first();
        $totalProducts = (int) ($productStats->total_products ?? 0);
        $totalStock = (int) ($productStats->total_stock ?? 0);

        // Gabungkan hitungan kategori induk & subkategori dalam 1 query
        $catStats = $store->categories()
            ->selectRaw('COUNT(CASE WHEN parent_id IS NULL THEN 1 END) as roots, COUNT(CASE WHEN parent_id IS NOT NULL THEN 1 END) as subs')
            ->first();
        $totalCategories = (int) ($catStats->roots ?? 0);
        $totalSubcategories = (int) ($catStats->subs ?? 0);

        $totalBatches = $store->purchaseBatches()->count();
        $lowStock = $store->products()->where('stock', '<=', 5)->orderBy('stock')->take(8)->get();
        $latestBatches = $store->purchaseBatches()->with('creator')->latest()->take(5)->get();
        $activity = AuditLog::where('store_id', $store->id)->with('user')->latest('created_at')->take(8)->get();

        return view('admin.dashboard', compact(
            'store', 'totalProducts', 'totalCategories', 'totalSubcategories',
            'totalBatches', 'totalStock', 'lowStock', 'latestBatches', 'activity'
        ));
    }
}
