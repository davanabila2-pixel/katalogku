<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'product_id',
        'purchase_batch_id',
        'movement_type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PurchaseBatch::class, 'purchase_batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
