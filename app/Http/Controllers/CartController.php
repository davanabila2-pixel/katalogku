<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Store;
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
     * Checkout seluruh item keranjang untuk toko yang dipilih.
     */
    public function checkout(Request $request, string $storeSlug, CartService $cartService): JsonResponse
    {
        $store = Store::where('slug', $storeSlug)->firstOrFail();

        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:100'],
            'buyer_phone' => ['required', 'string', 'max:25'],
            'buyer_address' => ['required', 'string', 'max:1000'],
            'buyer_notes' => ['nullable', 'string', 'max:1000'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $userId = auth()->id();
        $sessionId = $request->session()->getId();

        $cartItems = CartItem::with('product')
            ->where('store_id', $store->id)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! $userId, fn ($q) => $q->where('session_id', $sessionId))
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Keranjang belanja untuk toko ini kosong.',
            ], 422);
        }

        // Proteksi stok habis: pastikan tidak ada produk dengan stok habis atau melebihi kuota
        foreach ($cartItems as $item) {
            $product = $item->product;
            if (! $product || $product->stock <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Produk '{$item->product?->name}' sudah habis dan tidak dapat dibayar. Silakan hapus produk tersebut dari keranjang.",
                ], 422);
            }

            if ($item->quantity > $product->stock) {
                return response()->json([
                    'success' => false,
                    'message' => "Jumlah pesanan untuk '{$product->name}' ({$item->quantity}) melebihi stok yang tersedia ({$product->stock} unit).",
                ], 422);
            }
        }

        // Hitung total dan persiapkan data pesanan
        $totalPrice = 0;
        $totalQty = 0;
        $productNames = [];

        foreach ($cartItems as $item) {
            $sub = (float) $item->product->selling_price * $item->quantity;
            $totalPrice += $sub;
            $totalQty += $item->quantity;
            $productNames[] = "{$item->product->name} (x{$item->quantity})";
        }

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
            'buyer_name' => $data['buyer_name'],
            'buyer_phone' => $data['buyer_phone'],
            'buyer_address' => $data['buyer_address'],
            'buyer_notes' => $data['buyer_notes'] ?? null,
            'payment_method' => $store->hasStaticQr() ? 'qris' : 'transfer',
            'payment_proof_path' => $paymentProofPath,
            'status' => $paymentProofPath ? 'paid' : 'pending',
        ]);

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
            . "💵 *Total Pembayaran*: {$totalFormatted}\n"
            . "💳 *Metode*: " . ($hasProof ? 'QRIS Toko (Bukti Terunggah)' : 'Konfirmasi Pembayaran') . "\n\n"
            . "📋 *DATA PEMBELI*\n"
            . "👤 *Nama*: {$data['buyer_name']}\n"
            . "📱 *No. WA*: {$data['buyer_phone']}\n"
            . "📍 *Alamat*: {$data['buyer_address']}\n"
            . ($data['buyer_notes'] ? "📝 *Catatan*: {$data['buyer_notes']}\n\n" : "\n")
            . ($hasProof
                ? "Bukti transfer QRIS telah saya unggah di sistem. Mohon dicek dan diproses ya kak. Terima kasih!"
                : "Mohon info instruksi pembayaran / rekening untuk memproses pesanan keranjang ini. Terima kasih!");

        $waUrl = $storePhone ? "https://wa.me/{$storePhone}?text=" . urlencode($waMsg) : null;

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

