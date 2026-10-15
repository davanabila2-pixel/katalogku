<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseBatchItem extends Model
{
    protected $fillable = [
        'purchase_batch_id',
        'product_id',
        'quantity',
        'unit_purchase_price',
        'allocated_additional_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_purchase_price' => 'decimal:2',
            'allocated_additional_cost' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PurchaseBatch::class, 'purchase_batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subtotal(): float
    {
        return $this->quantity * (float) $this->unit_purchase_price;
    }

    public function totalWithAllocation(): float
    {
        return $this->subtotal() + (float) $this->allocated_additional_cost;
    }

    public function effectiveUnitCost(): ?float
    {
        if ($this->quantity <= 0) {
            return null;
        }

        return $this->totalWithAllocation() / $this->quantity;
    }
}
