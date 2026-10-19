<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseBatch;
use App\Models\PurchaseBatchItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Sumber kebenaran stok: kolom products.stock yang diperbarui secara
     * atomik (row lock) bersamaan dengan penulisan stock_movements dalam
     * satu transaksi. Keduanya tidak pernah diubah secara independen.
     */
    public function createBatch(array $batchData, array $items, User $user): PurchaseBatch
    {
        return DB::transaction(function () use ($batchData, $items, $user) {
            $batch = PurchaseBatch::create([
                'store_id' => $batchData['store_id'],
                'batch_code' => $batchData['batch_code'],
                'purchase_date' => $batchData['purchase_date'],
                'supplier_name' => $batchData['supplier_name'] ?? null,
                'notes' => $batchData['notes'] ?? null,
                'additional_cost' => $batchData['additional_cost'] ?? 0,
                'status' => 'active',
                'created_by' => $user->id,
            ]);

            $itemsSubtotal = collect($items)->sum(fn ($i) => $i['quantity'] * $i['unit_purchase_price']);
            $additional = (float) ($batchData['additional_cost'] ?? 0);

            foreach ($items as $row) {
                $product = Product::whereKey($row['product_id'])->lockForUpdate()->firstOrFail();

                if ((int) $product->store_id !== (int) $batch->store_id) {
                    throw ValidationException::withMessages([
                        'items' => "Produk ID {$product->id} bukan milik toko batch ini.",
                    ]);
                }

                if ($product->isBundle()) {
                    throw ValidationException::withMessages([
                        'items' => "Produk '{$product->name}' adalah produk setelan (bundle). Stoknya dihitung otomatis dari komponen dan tidak dapat dibeli via batch.",
                    ]);
                }

                $allocated = $itemsSubtotal > 0
                    ? round($additional * (($row['quantity'] * $row['unit_purchase_price']) / $itemsSubtotal), 2)
                    : 0;

                PurchaseBatchItem::create([
                    'purchase_batch_id' => $batch->id,
                    'product_id' => $product->id,
                    'quantity' => $row['quantity'],
                    'unit_purchase_price' => $row['unit_purchase_price'],
                    'allocated_additional_cost' => $allocated,
                    'notes' => $row['notes'] ?? null,
                ]);

                StockMovement::create([
                    'store_id' => $batch->store_id,
                    'product_id' => $product->id,
                    'purchase_batch_id' => $batch->id,
                    'movement_type' => 'in',
                    'quantity' => $row['quantity'],
                    'reference_type' => PurchaseBatch::class,
                    'reference_id' => $batch->id,
                    'notes' => 'Stok masuk batch '.$batch->batch_code,
                    'created_by' => $user->id,
                ]);

                $product->increment('stock', $row['quantity']);

                foreach ($product->partOfBundles as $bundle) {
                    $bundle->syncBundleStock();
                }
            }

            AuditLogger::log(
                'batch.created',
                $batch,
                "Batch {$batch->batch_code} dibuat (" . count($items) . ' item).',
                ['batch_code' => $batch->batch_code, 'total_cost' => $batch->fresh()->totalCost()],
                $user,
                $batch->store_id
            );

            return $batch->fresh('items');
        });
    }

    public function cancelBatch(PurchaseBatch $batch, User $user, ?string $reason = null): void
    {
        if ($batch->isCancelled()) {
            throw ValidationException::withMessages(['batch' => 'Batch sudah dibatalkan.']);
        }

        DB::transaction(function () use ($batch, $user, $reason) {
            $batch->loadMissing('items');

            foreach ($batch->items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();

                StockMovement::create([
                    'store_id' => $batch->store_id,
                    'product_id' => $product->id,
                    'purchase_batch_id' => $batch->id,
                    'movement_type' => 'cancel',
                    'quantity' => -$item->quantity,
                    'reference_type' => PurchaseBatch::class,
                    'reference_id' => $batch->id,
                    'notes' => 'Pembatalan batch '.$batch->batch_code.($reason ? ': '.$reason : ''),
                    'created_by' => $user->id,
                ]);

                $product->decrement('stock', $item->quantity);

                foreach ($product->partOfBundles as $bundle) {
                    $bundle->syncBundleStock();
                }
            }

            $batch->update(['status' => 'cancelled']);

            AuditLogger::log('batch.cancelled', $batch, "Batch {$batch->batch_code} dibatalkan.", ['reason' => $reason], $user, $batch->store_id);
        });
    }

    public function adjustStock(Product $product, int $quantity, User $user, ?string $reason = null): StockMovement
    {
        if ($product->isBundle()) {
            throw ValidationException::withMessages([
                'quantity' => "Produk '{$product->name}' adalah produk setelan (bundle). Stoknya mengikuti produk komponen penyusunnya dan tidak dapat dikoreksi manual.",
            ]);
        }

        if ($quantity === 0) {
            throw ValidationException::withMessages(['quantity' => 'Kuantitas koreksi tidak boleh nol.']);
        }

        if (empty($reason)) {
            throw ValidationException::withMessages(['reason' => 'Alasan koreksi wajib diisi.']);
        }

        return DB::transaction(function () use ($product, $quantity, $user, $reason) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            $movement = StockMovement::create([
                'store_id' => $product->store_id,
                'product_id' => $product->id,
                'movement_type' => 'adjustment',
                'quantity' => $quantity,
                'notes' => $reason,
                'created_by' => $user->id,
            ]);

            $product->increment('stock', $quantity);

            foreach ($product->partOfBundles as $bundle) {
                $bundle->syncBundleStock();
            }

            AuditLogger::log(
                'stock.adjusted',
                $product,
                "Stok {$product->name} dikoreksi {$quantity}. Alasan: {$reason}",
                ['quantity' => $quantity],
                $user,
                $product->store_id
            );

            return $movement;
        });
    }
}
