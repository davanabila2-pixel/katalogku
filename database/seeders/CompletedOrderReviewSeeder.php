<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CompletedOrderReviewSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::where('status', 'active')->get();

        $reviewsList = [
            [
                'buyer_name' => 'Budi Pratama',
                'buyer_phone' => '081298765432',
                'buyer_address' => 'Jl. Tebet Barat Dalam No. 18, Jakarta Selatan',
                'rating' => 5,
                'buyer_review' => 'Kualitas produk sangat bagus, jahitannya rapi dan bahannya nyaman dipakai seharian. Pengiriman cepat dan seller ramah sekali. Sangat puas!',
                'days_ago' => 2,
            ],
            [
                'buyer_name' => 'Siti Nurhaliza',
                'buyer_phone' => '085712348899',
                'buyer_address' => 'Komplek Graha Ceria Blok B-12, Bandung',
                'rating' => 5,
                'buyer_review' => 'Barang original sesuai deskripsi dan foto. Packaging aman terlindungi dan sampai tepat waktu. Pasti langganan di toko ini!',
                'days_ago' => 4,
            ],
            [
                'buyer_name' => 'Dimas Aditya',
                'buyer_phone' => '081388990011',
                'buyer_address' => 'Jl. Pemuda No. 45, Semarang',
                'rating' => 5,
                'buyer_review' => 'Modelnya keren dan kekinian. Ukuran sangat presisi dengan panduan ukuran. Recommended seller!',
                'days_ago' => 6,
            ],
        ];

        foreach ($stores as $store) {
            $products = Product::where('store_id', $store->id)
                ->where('status', 'active')
                ->take(2)
                ->get();

            if ($products->count() < 2) {
                // If fewer than 2 products, take any products or fallback
                $products = Product::where('store_id', $store->id)->take(2)->get();
            }

            if ($products->isEmpty()) {
                continue;
            }

            foreach ($products->values() as $index => $product) {
                $reviewData = $reviewsList[$index % count($reviewsList)];

                // Check if completed order already exists for this product in this store
                $existing = Order::where('store_id', $store->id)
                    ->where('product_id', $product->id)
                    ->where('status', 'completed')
                    ->first();

                $orderCode = 'ORD-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $store->slug), 0, 4)) . '-DONE-' . ($index + 1);

                if (! $existing) {
                    $order = Order::create([
                        'order_code' => $orderCode,
                        'store_id' => $store->id,
                        'product_id' => $product->id,
                        'user_id' => null,
                        'product_name' => $product->name,
                        'product_price' => $product->selling_price,
                        'quantity' => 1,
                        'total_price' => $product->selling_price,
                        'buyer_name' => $reviewData['buyer_name'],
                        'buyer_phone' => $reviewData['buyer_phone'],
                        'buyer_address' => $reviewData['buyer_address'],
                        'buyer_notes' => 'Tolong dicek sebelum kirim, terima kasih.',
                        'payment_method' => 'qris',
                        'payment_proof_path' => null,
                        'status' => 'completed',
                        'rating' => $reviewData['rating'],
                        'buyer_review' => $reviewData['buyer_review'],
                        'reviewed_at' => now()->subDays($reviewData['days_ago']),
                        'created_at' => now()->subDays($reviewData['days_ago'] + 1),
                        'updated_at' => now()->subDays($reviewData['days_ago']),
                    ]);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_price' => $product->selling_price,
                        'quantity' => 1,
                        'subtotal' => $product->selling_price,
                    ]);
                } else {
                    $existing->update([
                        'rating' => $reviewData['rating'],
                        'buyer_review' => $reviewData['buyer_review'],
                        'reviewed_at' => now()->subDays($reviewData['days_ago']),
                    ]);
                }
            }
        }
    }
}

