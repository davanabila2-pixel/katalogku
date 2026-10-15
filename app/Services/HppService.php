<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseBatchItem;

/**
 * Penilaian persediaan: RATA-RATA TERTIMBANG (weighted average).
 *
 * HPP per unit = total biaya perolehan (qty × harga beli + alokasi biaya
 * tambahan) dari seluruh batch aktif / total qty yang pernah dibeli.
 *
 * Metode ini dipilih karena sederhana, stabil untuk katalog thrift/fashion
 * dengan harga beli bervariasi antar batch, dan tidak bergantung pada asumsi
 * urutan keluar barang seperti FIFO. Jika bisnis membutuhkan FIFO di masa
 * depan, cukup ganti implementasi service ini tanpa mengubah skema.
 *
 * Selama belum ada penjualan, selisih harga jual − HPP disebut
 * "estimasi margin", bukan laba terealisasi.
 */
class HppService
{
    public function hppForProduct(Product $product): ?float
    {
        $items = PurchaseBatchItem::query()
            ->join('purchase_batches', 'purchase_batches.id', '=', 'purchase_batch_items.purchase_batch_id')
            ->where('purchase_batch_items.product_id', $product->id)
            ->where('purchase_batches.store_id', $product->store_id)
            ->where('purchase_batches.status', 'active')
            ->select('purchase_batch_items.quantity', 'purchase_batch_items.unit_purchase_price', 'purchase_batch_items.allocated_additional_cost')
            ->get();

        $totalQty = 0;
        $totalCost = 0.0;

        foreach ($items as $item) {
            $qty = (int) $item->quantity;
            if ($qty <= 0) {
                continue;
            }
            $totalQty += $qty;
            $totalCost += $qty * (float) $item->unit_purchase_price + (float) $item->allocated_additional_cost;
        }

        if ($totalQty <= 0) {
            return null;
        }

        return round($totalCost / $totalQty, 2);
    }

    public function marginForProduct(Product $product): ?float
    {
        $hpp = $this->hppForProduct($product);
        if ($hpp === null) {
            return null;
        }

        return round((float) $product->selling_price - $hpp, 2);
    }
}
