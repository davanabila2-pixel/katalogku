<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesImages;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class OrderSubmissionController extends Controller
{
    use HandlesImages;

    public function store(Request $request, string $storeSlug)
    {
        $store = Store::where('slug', $storeSlug)->firstOrFail();

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'buyer_name' => ['required', 'string', 'max:100'],
            'buyer_phone' => ['required', 'string', 'max:25'],
            'buyer_address' => ['required', 'string', 'max:1000'],
            'buyer_notes' => ['nullable', 'string', 'max:1000'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $product = Product::where('store_id', $store->id)
            ->where('id', $data['product_id'])
            ->where('status', 'active')
            ->firstOrFail();

        $qty = (int) $data['quantity'];
        $unitPrice = (float) $product->selling_price;
        $totalPrice = $unitPrice * $qty;

        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $paymentProofPath = $this->storeImage($store, $request->file('payment_proof'));
        }

        $orderCode = Order::generateOrderCode($store);

        $order = Order::create([
            'order_code' => $orderCode,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'product_name' => $product->name,
            'product_price' => $unitPrice,
            'quantity' => $qty,
            'total_price' => $totalPrice,
            'buyer_name' => $data['buyer_name'],
            'buyer_phone' => $data['buyer_phone'],
            'buyer_address' => $data['buyer_address'],
            'buyer_notes' => $data['buyer_notes'] ?? null,
            'payment_method' => $store->hasStaticQr() ? 'qris' : 'transfer',
            'payment_proof_path' => $paymentProofPath,
            'status' => $paymentProofPath ? 'paid' : 'pending',
        ]);

        $storePhone = $store->cleanPhoneNumber();
        $totalFormatted = 'Rp ' . number_format($totalPrice, 0, ',', '.');
        $hasProof = ! empty($paymentProofPath);

        $waMsg = "Halo {$store->name}, saya telah membuat pesanan di katalog Anda:\n\n"
            . "🛍️ *PESANAN RESMI*\n"
            . "🏷️ *No. Pesanan*: {$order->order_code}\n"
            . "📦 *Produk*: {$product->name}\n"
            . "🔢 *Jumlah*: {$qty} unit\n"
            . "💰 *Harga Satuan*: Rp " . number_format($unitPrice, 0, ',', '.') . "\n"
            . "💵 *Total Pembayaran*: {$totalFormatted}\n"
            . "💳 *Metode*: " . ($hasProof ? "QRIS Toko (Bukti Terunggah)" : "Konfirmasi Pembayaran") . "\n"
            . "🔗 *Link*: {$product->url($store)}\n\n"
            . "📋 *DATA PEMBELI*\n"
            . "👤 *Nama*: {$data['buyer_name']}\n"
            . "📱 *No. WA*: {$data['buyer_phone']}\n"
            . "📍 *Alamat*: {$data['buyer_address']}\n"
            . ($data['buyer_notes'] ? "📝 *Catatan*: {$data['buyer_notes']}\n\n" : "\n")
            . ($hasProof
                ? "Bukti transfer QRIS telah saya unggah di sistem. Mohon dicek dan diproses ya kak. Terima kasih!"
                : "Mohon info instruksi pembayaran / rekening untuk memproses pesanan ini. Terima kasih!");

        $waUrl = $storePhone ? "https://wa.me/{$storePhone}?text=" . urlencode($waMsg) : null;

        return response()->json([
            'success' => true,
            'order_code' => $order->order_code,
            'total_price_formatted' => $totalFormatted,
            'has_proof' => $hasProof,
            'whatsapp_url' => $waUrl,
            'message' => 'Pesanan berhasil dibuat!',
        ]);
    }
}

