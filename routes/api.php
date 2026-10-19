<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API JSON admin (same-origin)
|--------------------------------------------------------------------------
| Dipakai halaman-halaman Blade admin lewat fetch() supaya data bisa
| dimuat/diubah tanpa reload penuh. Middleware 'web' dipakai agar
| autentikasi memakai session login yang sama + proteksi CSRF;
| akses toko tetap divalidasi server lewat middleware 'store.access'.
*/

Route::middleware(['web', 'auth', 'store.access'])
    ->prefix('v1/stores/{store}')
    ->group(function () {
        Route::get('products', [ProductController::class, 'index'])->name('api.products.index');
    });
