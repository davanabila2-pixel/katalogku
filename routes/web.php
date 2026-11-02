<?php

use App\Http\Controllers\Admin\BatchController as AdminBatchController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\StockController as AdminStockController;
use App\Http\Controllers\Admin\StoreLinkController;
use App\Http\Controllers\Admin\StoreProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarcodeScanController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\OrderSubmissionController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StoreCustomerAuthController;
use App\Http\Controllers\StoreCustomerController;
use App\Http\Controllers\Superadmin\CatalogController as SuperadminCatalogController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\GoogleDriveController;
use App\Http\Controllers\Superadmin\StoreController as SuperadminStoreController;
use App\Http\Controllers\Superadmin\UserController as SuperadminUserController;
use App\Http\Controllers\UserOrderController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

// ---- Sistem (didefinisikan SEBELUM route katalog dinamis) ----
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/toko', [PublicController::class, 'stores'])->name('stores.index');
Route::get('/sitemap.xml', [PublicController::class, 'sitemap'])->name('sitemap');
Route::get('/scan/{code}', [BarcodeScanController::class, 'scan'])->name('product.scan');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/auth/google', [GoogleOAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleOAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [UserProfileController::class, 'edit'])->name('user.profile');
    Route::put('/profile', [UserProfileController::class, 'update'])->name('user.profile.update');
    Route::get('/orders', [UserOrderController::class, 'index'])->name('user.orders');
});

Route::get('/sanctum/csrf-cookie', function () {
    $response = response()->json(['status' => 'success']);
    return $response->withHeaders([
        'X-CSRF-TOKEN' => csrf_token(),
    ]);
});

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
    Route::get('drive/connect', [GoogleDriveController::class, 'connect'])->name('drive.connect');
    Route::get('drive/callback', [GoogleDriveController::class, 'callback'])->name('drive.callback');
});

// ---- Admin toko ----
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('no-store', [AuthController::class, 'noStore'])->name('nostore');
    Route::post('stores', [AuthController::class, 'createStore'])->name('stores.store');

    Route::middleware('store.access')->prefix('{store}')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('profil', [StoreProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profil', [StoreProfileController::class, 'update'])->name('profile.update');

        // Halaman tautan (gaya linktree): daftar link yang dibagikan ke pelanggan.
        Route::resource('links', StoreLinkController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::patch('links/{link}/move', [StoreLinkController::class, 'move'])->name('links.move');

        Route::resource('categories', AdminCategoryController::class)->except(['show']);
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::post('products/{product}/adjust', [AdminProductController::class, 'adjust'])->name('products.adjust');
        Route::post('products/{product}/barcode/regenerate', [AdminProductController::class, 'regenerateBarcode'])->name('products.barcode.regenerate');
        Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'destroyImage'])->name('products.images.destroy');

        Route::get('batches', [AdminBatchController::class, 'index'])->name('batches.index');
        Route::get('batches/create', [AdminBatchController::class, 'create'])->name('batches.create');
        Route::post('batches', [AdminBatchController::class, 'store'])->name('batches.store');
        Route::get('batches/{batch}', [AdminBatchController::class, 'show'])->name('batches.show');
        Route::post('batches/{batch}/cancel', [AdminBatchController::class, 'cancel'])->name('batches.cancel');
        Route::post('batches/{batch}/generate-qr', [AdminBatchController::class, 'generateQrCodes'])->name('batches.generate-qr');
        Route::get('batches/{batch}/labels', [AdminBatchController::class, 'labels'])->name('batches.labels');

        Route::get('stok', [AdminStockController::class, 'index'])->name('stock.index');
        Route::get('stok/riwayat', [AdminStockController::class, 'history'])->name('stock.history');
        Route::get('aktivitas', [AdminStockController::class, 'activity'])->name('activity');

        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    });
});

// ---- Keranjang Pintar Publik (Per-Akun / Sesi) ----
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/items/{id}', [CartController::class, 'update'])->name('update');
    Route::delete('/items/{id}', [CartController::class, 'remove'])->name('remove');
});

// ---- Checkout Keranjang per Toko & Pesanan Langsung ----
Route::post('/{storeSlug}/cart/checkout', [CartController::class, 'checkout'])
    ->where('storeSlug', '[a-z0-9][a-z0-9-]*')
    ->name('cart.checkout');

Route::post('/{storeSlug}/orders', [OrderSubmissionController::class, 'store'])
    ->where('storeSlug', '[a-z0-9][a-z0-9-]*')
    ->name('store.orders.store');

// ---- Pelanggan Toko (Auth, Profil, dan Pesanan per Toko) ----
Route::get('/{storeSlug}/masuk', [StoreCustomerAuthController::class, 'showLogin'])
    ->where('storeSlug', '[a-z0-9][a-z0-9-]*')->name('store.login');
Route::post('/{storeSlug}/masuk', [StoreCustomerAuthController::class, 'login'])
    ->where('storeSlug', '[a-z0-9][a-z0-9-]*')->name('store.login.submit');
Route::get('/{storeSlug}/daftar', [StoreCustomerAuthController::class, 'showRegister'])
    ->where('storeSlug', '[a-z0-9][a-z0-9-]*')->name('store.register');
Route::post('/{storeSlug}/daftar', [StoreCustomerAuthController::class, 'register'])
    ->where('storeSlug', '[a-z0-9][a-z0-9-]*')->name('store.register.submit');

Route::middleware('auth')->group(function () {
    Route::get('/{storeSlug}/profil', [StoreCustomerController::class, 'profile'])
        ->where('storeSlug', '[a-z0-9][a-z0-9-]*')->name('store.profile');
    Route::put('/{storeSlug}/profil', [StoreCustomerController::class, 'updateProfile'])
        ->where('storeSlug', '[a-z0-9][a-z0-9-]*')->name('store.profile.update');
    Route::get('/{storeSlug}/pesanan', [StoreCustomerController::class, 'orders'])
        ->where('storeSlug', '[a-z0-9][a-z0-9-]*')->name('store.orders');
});

Route::get('/{slug}', [PublicController::class, 'storeShow'])
    ->where('slug', '[a-z0-9][a-z0-9-]*')->name('store.show');
Route::get('/{storeSlug}/tautan', [PublicController::class, 'storeLinks'])
    ->where('storeSlug', '[a-z0-9][a-z0-9-]*')
    ->name('store.links');
Route::get('/{storeSlug}/terjual', [PublicController::class, 'storeSold'])
    ->where('storeSlug', '[a-z0-9][a-z0-9-]*')
    ->name('store.sold');
Route::get('/{storeSlug}/kategori/{categorySlug}/{subSlug?}', [PublicController::class, 'categoryShow'])
    ->where(['storeSlug' => '[a-z0-9][a-z0-9-]*', 'categorySlug' => '[a-z0-9][a-z0-9-]*', 'subSlug' => '[a-z0-9][a-z0-9-]*'])
    ->name('store.category');
Route::get('/{storeSlug}/produk/{productSlug}', [PublicController::class, 'productShow'])
    ->where(['storeSlug' => '[a-z0-9][a-z0-9-]*', 'productSlug' => '[a-z0-9][a-z0-9-]*'])
    ->name('store.product');
