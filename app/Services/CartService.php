<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * Dapatkan semua item keranjang pengguna (per-akun / sesi) dikelompokkan berdasarkan toko.
     */
    public function getCartData(Request $request, ?int $filterStoreId = null): array
    {
        $userId = auth()->id();
        $sessionId = $request->session()->getId();
        $storeId = $filterStoreId ?: ($request->filled('store_id') ? (int) $request->input('store_id') : null);

        // Jika user login, gabungkan item tamu dari sesi ke akun
        if ($userId) {
            $this->mergeGuestCartToUser($sessionId, $userId);
            $items = CartItem::with(['product', 'store'])
                ->where('user_id', $userId)
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
                ->get();
        } else {
            $items = CartItem::with(['product', 'store'])
                ->where('session_id', $sessionId)
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
                ->get();
        }

        $grouped = [];
        $totalQuantity = 0;
        $grandTotal = 0;
        $hasOutOfStock = false;

        foreach ($items as $item) {
            $product = $item->product;
            $store = $item->store;

            if (! $product || ! $store) {
                continue;
            }

            $currentStock = (int) $product->stock;
            $isOutOfStock = $currentStock <= 0;
            $exceedsStock = $item->quantity > $currentStock;

            if ($isOutOfStock || $exceedsStock) {
                $hasOutOfStock = true;
            }

            $subtotal = (float) $product->selling_price * $item->quantity;
            $totalQuantity += $item->quantity;
            $grandTotal += $subtotal;

            if (! isset($grouped[$store->id])) {
                $customerProfile = $userId ? auth()->user()->customerProfileFor($store->id) : null;
                $grouped[$store->id] = [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'store_slug' => $store->slug,
                    'store_phone' => $store->cleanPhoneNumber(),
                    'store_qr_url' => $store->qrImageUrl(),
                    'store_qr_name' => $store->qr_account_name,
                    'store_logo_url' => $store->logoUrl(),
                    'customer_profile' => $customerProfile ? [
                        'name' => $customerProfile->name,
                        'phone' => $customerProfile->phone,
                        'address' => $customerProfile->address,
                        'notes' => $customerProfile->notes,
                    ] : null,
                    'items' => [],
                    'store_subtotal' => 0,
                    'store_subtotal_formatted' => 'Rp 0',
                    'can_checkout' => true,
                    'out_of_stock_count' => 0,
                ];
            }

            if ($isOutOfStock || $exceedsStock) {
                $grouped[$store->id]['can_checkout'] = false;
                $grouped[$store->id]['out_of_stock_count']++;
            }

            $grouped[$store->id]['store_subtotal'] += $subtotal;
            $grouped[$store->id]['items'][] = [
                'id' => $item->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => (float) $product->selling_price,
                'price_formatted' => 'Rp ' . number_format($product->selling_price, 0, ',', '.'),
                'quantity' => $item->quantity,
                'stock' => $currentStock,
                'is_out_of_stock' => $isOutOfStock,
                'exceeds_stock' => $exceedsStock,
                'subtotal' => $subtotal,
                'subtotal_formatted' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
                'image_url' => $product->imageUrl(),
                'product_url' => $product->url($store),
            ];
        }

        foreach ($grouped as &$g) {
            $g['store_subtotal_formatted'] = 'Rp ' . number_format($g['store_subtotal'], 0, ',', '.');
        }
        unset($g);

        return [
            'items_count' => $items->count(),
            'total_quantity' => $totalQuantity,
            'grand_total' => $grandTotal,
            'grand_total_formatted' => 'Rp ' . number_format($grandTotal, 0, ',', '.'),
            'has_out_of_stock' => $hasOutOfStock,
            'stores' => array_values($grouped),
        ];
    }

    /**
     * Tambah produk ke keranjang pintar dengan validasi ketersediaan stok.
     */
    public function addToCart(Request $request, int $productId, int $quantity = 1): CartItem
    {
        $product = Product::with('store')->where('status', 'active')->findOrFail($productId);

        if ($product->stock <= 0) {
            throw ValidationException::withMessages([
                'product_id' => "Stok produk '{$product->name}' sudah habis dan tidak dapat ditambahkan ke keranjang.",
            ]);
        }

        $userId = auth()->id();
        $sessionId = $request->session()->getId();

        $query = CartItem::where('product_id', $product->id);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $item = $query->first();

        if ($item) {
            $newQuantity = $item->quantity + $quantity;
            if ($newQuantity > $product->stock) {
                $newQuantity = (int) $product->stock;
            }
            $item->update(['quantity' => $newQuantity]);
        } else {
            $initialQty = min($quantity, (int) $product->stock);
            $item = CartItem::create([
                'user_id' => $userId,
                'session_id' => $userId ? null : $sessionId,
                'store_id' => $product->store_id,
                'product_id' => $product->id,
                'quantity' => max(1, $initialQty),
            ]);
        }

        return $item->fresh(['product', 'store']);
    }

    /**
     * Perbarui jumlah item keranjang.
     */
    public function updateQuantity(Request $request, int $cartItemId, int $quantity): ?CartItem
    {
        $item = $this->findAuthorizedCartItem($request, $cartItemId);

        if ($quantity <= 0) {
            $item->delete();
            return null;
        }

        $product = $item->product;
        $maxStock = $product ? (int) $product->stock : 0;

        if ($maxStock <= 0) {
            // Stok habis, tidak dapat dinaikkan
            $item->update(['quantity' => 1]);
            return $item->fresh(['product', 'store']);
        }

        $validatedQty = min($quantity, $maxStock);
        $item->update(['quantity' => max(1, $validatedQty)]);

        return $item->fresh(['product', 'store']);
    }

    /**
     * Hapus item dari keranjang.
     */
    public function removeItem(Request $request, int $cartItemId): bool
    {
        $item = $this->findAuthorizedCartItem($request, $cartItemId);
        return (bool) $item->delete();
    }

    /**
     * Kosongkan item keranjang untuk toko tertentu setelah berhasil checkout.
     */
    public function clearStoreCart(Request $request, int $storeId): void
    {
        $userId = auth()->id();
        $sessionId = $request->session()->getId();

        $query = CartItem::where('store_id', $storeId);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $query->delete();
    }

    private function findAuthorizedCartItem(Request $request, int $cartItemId): CartItem
    {
        $userId = auth()->id();
        $sessionId = $request->session()->getId();

        $query = CartItem::with(['product', 'store'])->where('id', $cartItemId);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        return $query->firstOrFail();
    }

    private function mergeGuestCartToUser(string $sessionId, int $userId): void
    {
        $guestItems = CartItem::where('session_id', $sessionId)->get();

        foreach ($guestItems as $guestItem) {
            $existing = CartItem::where('user_id', $userId)
                ->where('product_id', $guestItem->product_id)
                ->first();

            if ($existing) {
                $product = $guestItem->product;
                $maxStock = $product ? (int) $product->stock : 999;
                $mergedQty = min($existing->quantity + $guestItem->quantity, $maxStock);
                $existing->update(['quantity' => $mergedQty]);
                $guestItem->delete();
            } else {
                $guestItem->update([
                    'user_id' => $userId,
                    'session_id' => null,
                ]);
            }
        }
    }
}

