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
        $totalProducts = $store->products()->count();
        $totalCategories = $store->categories()->whereNull('parent_id')->count();
        $totalSubcategories = $store->categories()->whereNotNull('parent_id')->count();
        $totalBatches = $store->purchaseBatches()->count();
        $totalStock = (int) $store->products()->sum('stock');
        $lowStock = $store->products()->where('stock', '<=', 5)->orderBy('stock')->take(8)->get();
        $latestBatches = $store->purchaseBatches()->with('creator')->latest()->take(5)->get();
        $activity = AuditLog::where('store_id', $store->id)->with('user')->latest('created_at')->take(8)->get();

        return view('admin.dashboard', compact(
            'store', 'totalProducts', 'totalCategories', 'totalSubcategories',
            'totalBatches', 'totalStock', 'lowStock', 'latestBatches', 'activity'
        ));
    }
}
