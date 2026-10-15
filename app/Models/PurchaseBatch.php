<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseBatch extends Model
{
    protected $fillable = [
        'store_id',
        'batch_code',
        'purchase_date',
        'supplier_name',
        'notes',
        'additional_cost',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'additional_cost' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseBatchItem::class);
    }

    public function itemsTotal(): float
    {
        return (float) $this->items->sum(fn ($item) => $item->quantity * (float) $item->unit_purchase_price);
    }

    public function totalCost(): float
    {
        return $this->itemsTotal() + (float) $this->additional_cost;
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
