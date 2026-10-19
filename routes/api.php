<?php

use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StoreProfileController;
use App\Http\Controllers\Api\Superadmin\CatalogController as SuperadminCatalogApiController;
use App\Http\Controllers\Api\Superadmin\StoreController as SuperadminStoreApiController;
use App\Http\Controllers\Api\Superadmin\UserController as SuperadminUserApiController;
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
        Route::get('products/form-data', [ProductController::class, 'formData'])->name('api.products.form_data');
        Route::get('products/{product}/form-data', [ProductController::class, 'productFormData'])->name('api.products.product_form_data');
        Route::post('products', [ProductController::class, 'store'])->name('api.products.store');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('api.products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('api.products.destroy');
        Route::post('products/{product}/adjust', [ProductController::class, 'adjust'])->name('api.products.adjust');
        Route::post('products/{product}/barcode', [ProductController::class, 'regenerateBarcode'])->name('api.products.barcode');

        Route::get('categories', [CategoryController::class, 'index'])->name('api.categories.index');
        Route::get('categories/options', [CategoryController::class, 'options'])->name('api.categories.options');
        Route::post('categories', [CategoryController::class, 'store'])->name('api.categories.store');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('api.categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('api.categories.destroy');

        Route::put('profile', [StoreProfileController::class, 'update'])->name('api.profile.update');

        Route::get('batches', [BatchController::class, 'index'])->name('api.batches.index');
        Route::get('batches/options', [BatchController::class, 'options'])->name('api.batches.options');
        Route::post('batches', [BatchController::class, 'store'])->name('api.batches.store');

        Route::get('stock', [StockController::class, 'index'])->name('api.stock.index');
        Route::get('stock/history', [StockController::class, 'history'])->name('api.stock.history');
        Route::get('stock/activity', [StockController::class, 'activity'])->name('api.stock.activity');
    });

// API superadmin (lintas toko) — hanya untuk role superadmin.
Route::middleware(['web', 'auth', 'superadmin'])
    ->prefix('v1/superadmin')
    ->name('api.superadmin.')
    ->group(function () {
        Route::get('stores', [SuperadminStoreApiController::class, 'index'])->name('stores.index');
        Route::post('stores', [SuperadminStoreApiController::class, 'store'])->name('stores.store');
        Route::get('stores/{store}/form-data', [SuperadminStoreApiController::class, 'formData'])->name('stores.form_data');
        Route::put('stores/{store}', [SuperadminStoreApiController::class, 'update'])->name('stores.update');

        Route::get('users', [SuperadminUserApiController::class, 'index'])->name('users.index');
        Route::get('users/{user}/form-data', [SuperadminUserApiController::class, 'formData'])->name('users.form_data');
        Route::put('users/{user}', [SuperadminUserApiController::class, 'update'])->name('users.update');

        Route::get('catalog/categories', [SuperadminCatalogApiController::class, 'categories'])->name('catalog.categories');
        Route::get('catalog/products', [SuperadminCatalogApiController::class, 'products'])->name('catalog.products');
        Route::get('catalog/batches', [SuperadminCatalogApiController::class, 'batches'])->name('catalog.batches');
        Route::get('catalog/stock', [SuperadminCatalogApiController::class, 'stock'])->name('catalog.stock');
        Route::get('catalog/audit', [SuperadminCatalogApiController::class, 'audit'])->name('catalog.audit');
    });
