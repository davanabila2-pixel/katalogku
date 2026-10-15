<?php

use App\Http\Controllers\Admin\BatchController as AdminBatchController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\StockController as AdminStockController;
use App\Http\Controllers\Admin\StoreProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Superadmin\CatalogController as SuperadminCatalogController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\StoreController as SuperadminStoreController;
use App\Http\Controllers\Superadmin\UserController as SuperadminUserController;
use Illuminate\Support\Facades\Route;

// ---- Sistem (didefinisikan SEBELUM route katalog dinamis) ----
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/toko', [PublicController::class, 'stores'])->name('stores.index');
Route::get('/sitemap.xml', [PublicController::class, 'sitemap'])->name('sitemap');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/auth/google', [GoogleOAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleOAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---- Superadmin ----
Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'superadmin'])->group(function () {
    Route::get('/', [SuperadminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('stores', SuperadminStoreController::class);
    Route::get('users', [SuperadminUserController::class, 'index'])->name('users.index');
    Route::get('users/{user}/edit', [SuperadminUserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [SuperadminUserController::class, 'update'])->name('users.update');
    Route::get('kategori', [SuperadminCatalogController::class, 'categories'])->name('categories');
    Route::get('produk', [SuperadminCatalogController::class, 'products'])->name('products');
    Route::get('batch', [SuperadminCatalogController::class, 'batches'])->name('batches');
    Route::get('stok', [SuperadminCatalogController::class, 'stock'])->name('stock');
    Route::get('audit', [SuperadminCatalogController::class, 'audit'])->name('audit');
});

// ---- Admin toko ----
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('no-store', [AuthController::class, 'noStore'])->name('nostore');

    Route::middleware('store.access')->prefix('{store}')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('profil', [StoreProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profil', [StoreProfileController::class, 'update'])->name('profile.update');

        Route::resource('categories', AdminCategoryController::class)->except(['show']);
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::post('products/{product}/adjust', [AdminProductController::class, 'adjust'])->name('products.adjust');

        Route::get('batches', [AdminBatchController::class, 'index'])->name('batches.index');
        Route::get('batches/create', [AdminBatchController::class, 'create'])->name('batches.create');
        Route::post('batches', [AdminBatchController::class, 'store'])->name('batches.store');
        Route::get('batches/{batch}', [AdminBatchController::class, 'show'])->name('batches.show');
        Route::post('batches/{batch}/cancel', [AdminBatchController::class, 'cancel'])->name('batches.cancel');

        Route::get('stok', [AdminStockController::class, 'index'])->name('stock.index');
        Route::get('stok/riwayat', [AdminStockController::class, 'history'])->name('stock.history');
        Route::get('aktivitas', [AdminStockController::class, 'activity'])->name('activity');
    });
});

// ---- Katalog publik per toko (PALING AKHIR agar tidak menelan route sistem) ----
Route::get('/{slug}', [PublicController::class, 'storeShow'])
    ->where('slug', '[a-z0-9][a-z0-9-]*')->name('store.show');
Route::get('/{storeSlug}/kategori/{categorySlug}/{subSlug?}', [PublicController::class, 'categoryShow'])
    ->where(['storeSlug' => '[a-z0-9][a-z0-9-]*', 'categorySlug' => '[a-z0-9][a-z0-9-]*', 'subSlug' => '[a-z0-9][a-z0-9-]*'])
    ->name('store.category');
Route::get('/{storeSlug}/produk/{productSlug}', [PublicController::class, 'productShow'])
    ->where(['storeSlug' => '[a-z0-9][a-z0-9-]*', 'productSlug' => '[a-z0-9][a-z0-9-]*'])
    ->name('store.product');
