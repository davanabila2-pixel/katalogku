<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /*
     * Halaman-halaman katalog lintas toko kini hanya merender kerangka;
     * datanya dimuat lewat API JSON (Api\Superadmin\CatalogController)
     * supaya filter/pagination berjalan tanpa reload halaman.
     * Daftar toko tetap dirender server-side untuk mengisi dropdown filter.
     */

    public function categories(Request $request)
    {
        $stores = Store::orderBy('name')->get(['id', 'name']);

        return view('superadmin.catalog.categories', compact('stores'));
    }

    public function products(Request $request)
    {
        $stores = Store::orderBy('name')->get(['id', 'name']);

        return view('superadmin.catalog.products', compact('stores'));
    }

    public function batches(Request $request)
    {
        $stores = Store::orderBy('name')->get(['id', 'name']);

        return view('superadmin.catalog.batches', compact('stores'));
    }

    public function stock(Request $request)
    {
        $stores = Store::orderBy('name')->get(['id', 'name']);

        return view('superadmin.catalog.stock', compact('stores'));
    }

    public function audit(Request $request)
    {
        $stores = Store::orderBy('name')->get(['id', 'name']);

        return view('superadmin.catalog.audit', compact('stores'));
    }
}
