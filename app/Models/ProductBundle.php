<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductBundle extends Pivot
{
    protected $table = 'product_bundles';

    protected $fillable = [
        'bundle_id',
        'item_product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'bundle_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_product_id');
    }
}
