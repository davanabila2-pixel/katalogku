<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'store_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isOutOfStock(): bool
    {
        return ! $this->product || $this->product->stock <= 0;
    }

    public function hasSufficientStock(): bool
    {
        return $this->product && $this->product->stock >= $this->quantity;
    }

    public function availableStock(): int
    {
        return $this->product ? (int) $this->product->stock : 0;
    }

    public function subtotal(): float
    {
        if (! $this->product) {
            return 0.0;
        }

        return (float) $this->product->selling_price * $this->quantity;
    }

    public function subtotalFormatted(): string
    {
        return 'Rp ' . number_format($this->subtotal(), 0, ',', '.');
    }
}

