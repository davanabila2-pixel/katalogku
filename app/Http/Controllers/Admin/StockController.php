<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request, Store $store)
    {
        // Daftar dimuat lewat API JSON (App\Http\Controllers\Api\StockController)
        // agar filter/pencarian/pagination berjalan tanpa reload halaman.
        return view('admin.stock.index', compact('store'));
    }

    public function history(Request $request, Store $store)
    {
        // Daftar mutasi dimuat lewat API JSON; produk tetap dirender server-side
        // hanya untuk mengisi dropdown filter.
        $products = Product::forStore($store->id)->orderBy('name')->get(['id', 'name']);

        return view('admin.stock.history', compact('store', 'products'));
    }

    public function activity(Request $request, Store $store)
    {
        // Daftar aktivitas dimuat lewat API JSON (Api\StockController@activity).
        return view('admin.stock.activity', compact('store'));
    }
}
