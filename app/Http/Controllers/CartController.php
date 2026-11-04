<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCustomerAddress;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    use HandlesImages;

    /**
     * Dapatkan ringkasan keranjang pintar saat ini (per-akun / sesi).
     */
    public function index(Request $request, CartService $cartService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'cart' => $cartService->getCartData($request),
        ]);
    }

    /**
     * Tambahkan produk ke keranjang pintar.
     */
    public function add(Request $request, CartService $cartService): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $qty = (int) ($validated['quantity'] ?? 1);

        try {
            $item = $cartService->addToCart($request, (int) $validated['product_id'], $qty);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Gagal menambahkan produk ke keranjang.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Produk '{$item->product->name}' berhasil ditambahkan ke keranjang.",
            'cart' => $cartService->getCartData($request),
        ]);
    }

    /**
     * Perbarui jumlah produk di keranjang.
     */
    public function update(Request $request, int $id, CartService $cartService): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $cartService->updateQuantity($request, $id, (int) $validated['quantity']);

        return response()->json([
            'success' => true,
            'cart' => $cartService->getCartData($request),
        ]);
    }

    /**
     * Hapus item dari keranjang.
     */
    public function remove(Request $request, int $id, CartService $cartService): JsonResponse
    {
        $cartService->removeItem($request, $id);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus dari keranjang.',
            'cart' => $cartService->getCartData($request),
        ]);
    }

    /**
     * Tampilkan halaman checkout mandiri untuk toko tertentu.
     */
    public function showCheckout(Request $request, string $storeSlug, CartService $cartService)
    {
        $store = Store::where('slug', $storeSlug)->firstOrFail();

        if (! $store->isActive()) {
            abort(404);
        }

        $productId = $request->query('product_id');
        $directProduct = null;
        $items = [];
        $totalPrice = 0;
        $totalQty = 0;
        $isDirectBuy = false;
        $hasOutOfStock = false;

        if ($productId) {
            $directProduct = Product::where('store_id', $store->id)
                ->where('id', $productId)
                ->publiclyVisible()
                ->first();

            if ($directProduct) {
                $isDirectBuy = true;
                $requestedQty = (int) $request->query('quantity', 1);
                $qty = max(1, $requestedQty);
                $isOutOfStock = $directProduct->stock <= 0;
                $exceedsStock = $qty > $directProduct->stock;
                $hasOutOfStock = $isOutOfStock || $exceedsStock;

                $subtotal = (float) $directProduct->selling_price * $qty;
                $items[] = [
                    'id' => null,
                    'product_id' => $directProduct->id,
                    'name' => $directProduct->name,
                    'slug' => $directProduct->slug,
                    'price' => (float) $directProduct->selling_price,
                    'price_formatted' => 'Rp ' . number_format($directProduct->selling_price, 0, ',', '.'),
                    'quantity' => $qty,
                    'stock' => (int) $directProduct->stock,
                    'is_out_of_stock' => $isOutOfStock,
                    'exceeds_stock' => $exceedsStock,
                    'subtotal' => $subtotal,
                    'subtotal_formatted' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
                    'image_url' => $directProduct->imageUrl(),
                    'product_url' => $directProduct->url($store),
                ];
                $totalPrice = $subtotal;
                $totalQty = $qty;
            }
        }

        if (! $isDirectBuy) {
            $cartData = $cartService->getCartData($request, $store->id);
            $storeCart = collect($cartData['stores'])->firstWhere('store_id', $store->id);
            if ($storeCart) {
                $items = $storeCart['items'];
                $totalPrice = $storeCart['store_subtotal'];
                $totalQty = collect($items)->sum('quantity');
                $hasOutOfStock = ! $storeCart['can_checkout'];
            }
        }

        $user = auth()->user();
        $customerProfile = $user ? $user->customerProfileFor($store->id) : null;
        $savedAddresses = collect();

        if ($user) {
            $savedAddresses = StoreCustomerAddress::where('store_id', $store->id)
                ->where('user_id', $user->id)
                ->orderByDesc('is_default')
                ->latest()
                ->get();

            // Jika belum ada di store_customer_addresses untuk toko ini:
            if ($savedAddresses->isEmpty()) {
                if ($customerProfile && ! empty($customerProfile->address)) {
                    $newAddr = StoreCustomerAddress::create([
                        'store_id' => $store->id,
                        'user_id' => $user->id,
                        'label' => 'Alamat Utama',
                        'recipient_name' => $customerProfile->name ?: $user->name,
                        'phone' => $customerProfile->phone ?: '',
                        'address' => $customerProfile->address,
                        'postal_code' => $customerProfile->postal_code,
                        'city' => $customerProfile->city,
                        'district' => $customerProfile->district,
                        'latitude' => $customerProfile->latitude,
                        'longitude' => $customerProfile->longitude,
                        'notes' => $customerProfile->notes,
                        'is_default' => true,
                    ]);
                    $savedAddresses = collect([$newAddr]);
                } else {
                    $anyAddress = StoreCustomerAddress::where('user_id', $user->id)
                        ->orderByDesc('is_default')
                        ->latest()
                        ->first();
                    if ($anyAddress) {
                        $newAddr = StoreCustomerAddress::create([
                            'store_id' => $store->id,
                            'user_id' => $user->id,
                            'label' => $anyAddress->label ?: 'Alamat Utama',
                            'recipient_name' => $anyAddress->recipient_name ?: $user->name,
                            'phone' => $anyAddress->phone ?: '',
                            'address' => $anyAddress->address,
                            'postal_code' => $anyAddress->postal_code,
                            'city' => $anyAddress->city,
                            'district' => $anyAddress->district,
                            'latitude' => $anyAddress->latitude,
                            'longitude' => $anyAddress->longitude,
                            'notes' => $anyAddress->notes,
                            'is_default' => true,
                        ]);
                        $savedAddresses = collect([$newAddr]);
                    }
                }
            }
        }

        $googleMapsApiKey = config('services.google_maps.key', env('GOOGLE_MAPS_API_KEY', ''));

        $seo = [
            'title' => "Checkout Pesanan: {$store->name}",
            'description' => "Checkout dan konfirmasi pesanan belanja di {$store->name}.",
            'canonical' => route('store.checkout', $store->slug),
            'image' => $store->logoUrl(),
        ];

        return view('public.checkout', compact(
            'store',
            'items',
            'totalPrice',
            'totalQty',
            'isDirectBuy',
            'directProduct',
            'hasOutOfStock',
            'customerProfile',
            'savedAddresses',
            'googleMapsApiKey',
            'seo'
        ));
    }

    /**
     * Checkout seluruh item keranjang atau produk langsung untuk toko yang dipilih.
     */
    public function checkout(Request $request, string $storeSlug, CartService $cartService)
    {
        $store = Store::where('slug', $storeSlug)->firstOrFail();

        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:100'],
            'buyer_phone' => ['required', 'string', 'max:25'],
            'buyer_address' => ['required', 'string', 'max:1000'],
            'buyer_postal_code' => ['nullable', 'string', 'max:10'],
            'buyer_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'buyer_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'buyer_notes' => ['nullable', 'string', 'max:1000'],
            'shipping_courier' => ['nullable', 'string', 'max:50'],
            'shipping_service' => ['nullable', 'string', 'max:50'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'shipping_etd' => ['nullable', 'string', 'max:50'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        // Master ekspedisi: tolak ekspedisi yang sudah dinonaktifkan admin toko.
        if (! empty($data['shipping_courier'])) {
            $chosen = strtolower($data['shipping_courier']);
            $blocked = false;
            foreach (\App\Support\Couriers::all() as $code => $info) {
                $matches = $chosen === $code || $chosen === strtolower($info['name']) || str_contains($chosen, $code);
                if ($matches && ! in_array($code, $store->activeCourierCodes(), true)) {
                    $blocked = true;
                }
            }
            if ($blocked) {
                $msg = 'Ekspedisi yang dipilih tidak aktif di toko ini. Silakan pilih ekspedisi lain.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withInput()->withErrors(['checkout' => $msg]);
            }
        }

        $userId = auth()->id();
        $sessionId = $request->session()->getId();

        // Mode 1: Pembelian Langsung (Direct Buy) 1 Produk
        if (! empty($data['product_id'])) {
            $product = Product::where('store_id', $store->id)
                ->where('id', $data['product_id'])
                ->where('status', 'active')
                ->firstOrFail();

            $qty = (int) ($data['quantity'] ?? 1);

            if ($product->stock <= 0) {
                $msg = "Maaf, stok produk '{$product->name}' sudah habis dan tidak dapat dibayar.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withInput()->withErrors(['checkout' => $msg]);
            }

            if ($qty > $product->stock) {
                $msg = "Jumlah pesanan ({$qty}) melebihi stok yang tersedia ({$product->stock} unit).";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withInput()->withErrors(['checkout' => $msg]);
            }

            $unitPrice = (float) $product->selling_price;
            $itemsSubtotal = $unitPrice * $qty;
            $shippingFee = max(0, (float) ($data['shipping_fee'] ?? 0));
            $totalPrice = $itemsSubtotal + $shippingFee;

            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $paymentProofPath = $this->storeImage($store, $request->file('payment_proof'));
            }

            $orderCode = Order::generateOrderCode($store);

            $order = Order::create([
                'order_code' => $orderCode,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'user_id' => $userId,
                'product_name' => "{$product->name} (x{$qty})",
                'product_price' => $unitPrice,
                'quantity' => $qty,
                'total_price' => $totalPrice,
                'shipping_courier' => $data['shipping_courier'] ?? null,
                'shipping_service' => $data['shipping_service'] ?? null,
                'shipping_fee' => $shippingFee,
                'shipping_etd' => $data['shipping_etd'] ?? null,
                'buyer_name' => $data['buyer_name'],
                'buyer_phone' => $data['buyer_phone'],
                'buyer_address' => $data['buyer_address'],
                'buyer_postal_code' => $data['buyer_postal_code'] ?? null,
                'buyer_latitude' => $data['buyer_latitude'] ?? null,
                'buyer_longitude' => $data['buyer_longitude'] ?? null,
                'buyer_notes' => $data['buyer_notes'] ?? null,
                'payment_method' => $store->hasStaticQr() ? 'qris' : 'transfer',
                'payment_proof_path' => $paymentProofPath,
                'status' => $paymentProofPath ? 'paid' : 'pending',
            ]);

            if ($userId) {
                \App\Models\StoreCustomerProfile::updateOrCreate(
                    ['store_id' => $store->id, 'user_id' => $userId],
                    array_filter([
                        'name' => $data['buyer_name'],
                        'phone' => $data['buyer_phone'],
                        'address' => $data['buyer_address'],
                        'notes' => $data['buyer_notes'] ?? null,
                        'postal_code' => $data['buyer_postal_code'] ?? null,
                        'latitude' => $data['buyer_latitude'] ?? null,
                        'longitude' => $data['buyer_longitude'] ?? null,
                    ], fn ($val) => ! is_null($val))
                );

                $existingAddr = StoreCustomerAddress::where('store_id', $store->id)
                    ->where('user_id', $userId)
                    ->where('address', $data['buyer_address'])
                    ->first();
                if (! $existingAddr) {
                    $hasAny = StoreCustomerAddress::where('store_id', $store->id)
                        ->where('user_id', $userId)
                        ->exists();
                    StoreCustomerAddress::create([
                        'store_id' => $store->id,
                        'user_id' => $userId,
                        'label' => $hasAny ? 'Alamat Pengiriman' : 'Alamat Utama',
                        'recipient_name' => $data['buyer_name'],
                        'phone' => $data['buyer_phone'],
                        'address' => $data['buyer_address'],
                        'postal_code' => $data['buyer_postal_code'] ?? null,
                        'latitude' => $data['buyer_latitude'] ?? null,
                        'longitude' => $data['buyer_longitude'] ?? null,
                        'notes' => $data['buyer_notes'] ?? null,
                        'is_default' => ! $hasAny,
                    ]);
                }
            }

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_price' => $unitPrice,
                'quantity' => $qty,
                'subtotal' => $itemsSubtotal,
            ]);

            $product->decrement('stock', $qty);
            foreach ($product->partOfBundles as $bundle) {
                $bundle->syncBundleStock();
            }

            // Hapus produk tersebut dari keranjang jika ada
            CartItem::where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
                ->when(! $userId, fn ($q) => $q->where('session_id', $sessionId))
                ->delete();

            $storePhone = $store->cleanPhoneNumber();
            $totalFormatted = 'Rp ' . number_format($totalPrice, 0, ',', '.');
            $hasProof = ! empty($paymentProofPath);

            $shippingText = '';
            if (! empty($data['shipping_courier'])) {
                $courierUpper = strtoupper($data['shipping_courier']);
                $serv = ($data['shipping_service'] ?? null) ? " ({$data['shipping_service']})" : '';
                $etd = ($data['shipping_etd'] ?? null) ? " [Estimasi {$data['shipping_etd']}]" : '';
                $ongkirFormatted = 'Rp ' . number_format($shippingFee, 0, ',', '.');
                $shippingText = "🚚 *Pengiriman*: {$courierUpper}{$serv}{$etd}\n"
                    . "📦 *Ongkir*: {$ongkirFormatted}\n";
            }

            $waMsg = "Halo {$store->name}, saya telah melakukan pemesanan melalui katalog:\n\n"
                . "🛍️ *PESANAN TOKO*\n"
                . "🏷️ *No. Pesanan*: {$order->order_code}\n"
                . "📦 *Produk*: {$product->name} (x{$qty})\n"
                . ($shippingText ? "💰 *Subtotal Produk*: Rp " . number_format($itemsSubtotal, 0, ',', '.') . "\n" . $shippingText : "")
                . "💵 *Total Pembayaran*: {$totalFormatted}\n"
                . "💳 *Metode*: " . ($hasProof ? 'QRIS Toko (Bukti Terunggah)' : 'Konfirmasi Pembayaran') . "\n\n"
                . "📋 *DATA PEMBELI*\n"
                . "👤 *Nama*: {$data['buyer_name']}\n"
                . "📱 *No. WA*: {$data['buyer_phone']}\n"
                . "📍 *Alamat*: {$data['buyer_address']}\n"
                . (($data['buyer_notes'] ?? null) ? "📝 *Catatan*: {$data['buyer_notes']}\n\n" : "\n")
                . ($hasProof
                    ? "Bukti transfer QRIS telah saya unggah di sistem. Mohon dicek dan diproses ya kak. Terima kasih!"
                    : "Mohon info instruksi pembayaran / rekening untuk memproses pesanan ini. Terima kasih!");

            $waUrl = $storePhone ? "https://wa.me/{$storePhone}?text=" . urlencode($waMsg) : null;

            if (! $request->expectsJson() && ! $request->ajax()) {
                return redirect()->route('store.checkout', $store->slug)->with('order_success', [
                    'order_code' => $order->order_code,
                    'total_price_formatted' => $totalFormatted,
                    'has_proof' => $hasProof,
                    'whatsapp_url' => $waUrl,
                ]);
            }

            return response()->json([
                'success' => true,
                'order_code' => $order->order_code,
                'total_price_formatted' => $totalFormatted,
                'has_proof' => $hasProof,
                'whatsapp_url' => $waUrl,
                'message' => 'Pesanan berhasil dibuat!',
                'cart' => $cartService->getCartData($request),
            ]);
        }

        // Mode 2: Checkout Keranjang Belanja Toko
        $cartItems = CartItem::with('product')
            ->where('store_id', $store->id)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->where('session_id', $sessionId))
            ->get();

        if ($cartItems->isEmpty()) {
            $msg = 'Keranjang belanja untuk toko ini kosong.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withInput()->withErrors(['checkout' => $msg]);
        }

        // Proteksi stok habis: pastikan tidak ada produk dengan stok habis atau melebihi kuota
        foreach ($cartItems as $item) {
            $product = $item->product;
            if (! $product || $product->stock <= 0) {
                $msg = "Produk '{$item->product?->name}' sudah habis dan tidak dapat dibayar. Silakan hapus produk tersebut dari keranjang.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withInput()->withErrors(['checkout' => $msg]);
            }

            if ($item->quantity > $product->stock) {
                $msg = "Jumlah pesanan untuk '{$product->name}' ({$item->quantity}) melebihi stok yang tersedia ({$product->stock} unit).";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withInput()->withErrors(['checkout' => $msg]);
            }
        }

        // Hitung total dan persiapkan data pesanan
        $itemsSubtotal = 0;
        $totalQty = 0;
        $productNames = [];

        foreach ($cartItems as $item) {
            $sub = (float) $item->product->selling_price * $item->quantity;
            $itemsSubtotal += $sub;
            $totalQty += $item->quantity;
            $productNames[] = "{$item->product->name} (x{$item->quantity})";
        }

        $shippingFee = max(0, (float) ($data['shipping_fee'] ?? 0));
        $totalPrice = $itemsSubtotal + $shippingFee;

        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $paymentProofPath = $this->storeImage($store, $request->file('payment_proof'));
        }

        $orderCode = Order::generateOrderCode($store);
        $primaryProduct = $cartItems->first()->product;

        $order = Order::create([
            'order_code' => $orderCode,
            'store_id' => $store->id,
            'product_id' => $primaryProduct->id,
            'user_id' => $userId,
            'product_name' => implode(', ', $productNames),
            'product_price' => (float) $primaryProduct->selling_price,
            'quantity' => $totalQty,
            'total_price' => $totalPrice,
            'shipping_courier' => $data['shipping_courier'] ?? null,
            'shipping_service' => $data['shipping_service'] ?? null,
            'shipping_fee' => $shippingFee,
            'shipping_etd' => $data['shipping_etd'] ?? null,
            'buyer_name' => $data['buyer_name'],
            'buyer_phone' => $data['buyer_phone'],
            'buyer_address' => $data['buyer_address'],
            'buyer_postal_code' => $data['buyer_postal_code'] ?? null,
            'buyer_latitude' => $data['buyer_latitude'] ?? null,
            'buyer_longitude' => $data['buyer_longitude'] ?? null,
            'buyer_notes' => $data['buyer_notes'] ?? null,
            'payment_method' => $store->hasStaticQr() ? 'qris' : 'transfer',
            'payment_proof_path' => $paymentProofPath,
            'status' => $paymentProofPath ? 'paid' : 'pending',
        ]);

        if ($userId) {
            \App\Models\StoreCustomerProfile::updateOrCreate(
                ['store_id' => $store->id, 'user_id' => $userId],
                array_filter([
                    'name' => $data['buyer_name'],
                    'phone' => $data['buyer_phone'],
                    'address' => $data['buyer_address'],
                    'notes' => $data['buyer_notes'] ?? null,
                    'postal_code' => $data['buyer_postal_code'] ?? null,
                    'latitude' => $data['buyer_latitude'] ?? null,
                    'longitude' => $data['buyer_longitude'] ?? null,
                ], fn ($val) => ! is_null($val))
            );

            $existingAddr = StoreCustomerAddress::where('store_id', $store->id)
                ->where('user_id', $userId)
                ->where('address', $data['buyer_address'])
                ->first();
            if (! $existingAddr) {
                $hasAny = StoreCustomerAddress::where('store_id', $store->id)
                    ->where('user_id', $userId)
                    ->exists();
                StoreCustomerAddress::create([
                    'store_id' => $store->id,
                    'user_id' => $userId,
                    'label' => $hasAny ? 'Alamat Pengiriman' : 'Alamat Utama',
                    'recipient_name' => $data['buyer_name'],
                    'phone' => $data['buyer_phone'],
                    'address' => $data['buyer_address'],
                    'postal_code' => $data['buyer_postal_code'] ?? null,
                    'latitude' => $data['buyer_latitude'] ?? null,
                    'longitude' => $data['buyer_longitude'] ?? null,
                    'notes' => $data['buyer_notes'] ?? null,
                    'is_default' => ! $hasAny,
                ]);
            }
        }

        // Simpan rincian item pesanan & kurangi stok produk
        foreach ($cartItems as $item) {
            $product = $item->product;
            $itemSubtotal = (float) $product->selling_price * $item->quantity;

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_price' => $product->selling_price,
                'quantity' => $item->quantity,
                'subtotal' => $itemSubtotal,
            ]);

            // Pengurangan stok otomatis
            $product->decrement('stock', $item->quantity);
            foreach ($product->partOfBundles as $bundle) {
                $bundle->syncBundleStock();
            }
        }

        // Kosongkan item keranjang untuk toko ini
        $cartService->clearStoreCart($request, $store->id);

        $storePhone = $store->cleanPhoneNumber();
        $totalFormatted = 'Rp ' . number_format($totalPrice, 0, ',', '.');
        $hasProof = ! empty($paymentProofPath);

        $shippingCartText = '';
        if (! empty($data['shipping_courier'])) {
            $courierUpper = strtoupper($data['shipping_courier']);
            $serv = ($data['shipping_service'] ?? null) ? " ({$data['shipping_service']})" : '';
            $etd = ($data['shipping_etd'] ?? null) ? " [Estimasi {$data['shipping_etd']}]" : '';
            $ongkirFormatted = 'Rp ' . number_format($shippingFee, 0, ',', '.');
            $shippingCartText = "💰 *Subtotal Produk*: Rp " . number_format($itemsSubtotal, 0, ',', '.') . "\n"
                . "🚚 *Pengiriman*: {$courierUpper}{$serv}{$etd}\n"
                . "📦 *Ongkos Kirim*: {$ongkirFormatted}\n";
        }

        // Format pesan WhatsApp
        $itemListText = '';
        foreach ($cartItems as $idx => $it) {
            $num = $idx + 1;
            $itemSub = 'Rp ' . number_format($it->product->selling_price * $it->quantity, 0, ',', '.');
            $itemListText .= "  {$num}. {$it->product->name} (x{$it->quantity}) = {$itemSub}\n";
        }

        $waMsg = "Halo {$store->name}, saya telah memesan melalui keranjang belanja katalog:\n\n"
            . "🛍️ *PESANAN KERANJANG*\n"
            . "🏷️ *No. Pesanan*: {$order->order_code}\n"
            . "📦 *Daftar Barang*:\n{$itemListText}"
            . "🔢 *Total Item*: {$totalQty} unit\n"
            . ($shippingCartText ?: '')
            . "💵 *Total Pembayaran*: {$totalFormatted}\n"
            . "💳 *Metode*: " . ($hasProof ? 'QRIS Toko (Bukti Terunggah)' : 'Konfirmasi Pembayaran') . "\n\n"
            . "📋 *DATA PEMBELI*\n"
            . "👤 *Nama*: {$data['buyer_name']}\n"
            . "📱 *No. WA*: {$data['buyer_phone']}\n"
            . "📍 *Alamat*: {$data['buyer_address']}\n"
            . (($data['buyer_notes'] ?? null) ? "📝 *Catatan*: {$data['buyer_notes']}\n\n" : "\n")
            . ($hasProof
                ? "Bukti transfer QRIS telah saya unggah di sistem. Mohon dicek dan diproses ya kak. Terima kasih!"
                : "Mohon info instruksi pembayaran / rekening untuk memproses pesanan keranjang ini. Terima kasih!");

        $waUrl = $storePhone ? "https://wa.me/{$storePhone}?text=" . urlencode($waMsg) : null;

        if (! $request->expectsJson() && ! $request->ajax()) {
            return redirect()->route('store.checkout', $store->slug)->with('order_success', [
                'order_code' => $order->order_code,
                'total_price_formatted' => $totalFormatted,
                'has_proof' => $hasProof,
                'whatsapp_url' => $waUrl,
            ]);
        }

        return response()->json([
            'success' => true,
            'order_code' => $order->order_code,
            'total_price_formatted' => $totalFormatted,
            'has_proof' => $hasProof,
            'whatsapp_url' => $waUrl,
            'message' => 'Pesanan keranjang berhasil dibuat!',
            'cart' => $cartService->getCartData($request),
        ]);
    }
}

