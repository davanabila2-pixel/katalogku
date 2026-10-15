<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseBatch;
use App\Models\Store;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'stores' => Store::count(),
            'stores_active' => Store::where('status', 'active')->count(),
            'users' => User::count(),
            'products' => Product::count(),
            'categories' => Category::count(),
            'batches' => PurchaseBatch::count(),
            'stock' => (int) Product::sum('stock'),
        ];

        $latestStores = Store::withCount(['products', 'categories'])->latest()->take(5)->get();
        $activity = AuditLog::with(['user', 'store'])->latest('created_at')->take(10)->get();

        return view('superadmin.dashboard', compact('stats', 'latestStores', 'activity'));
    }
}
