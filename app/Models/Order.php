<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_code',
        'store_id',
        'product_id',
        'user_id',
        'product_name',
        'product_price',
        'quantity',
        'total_price',
        'buyer_name',
        'buyer_phone',
        'buyer_address',
        'buyer_notes',
        'payment_method',
        'payment_proof_path',
        'status',
        'rating',
        'buyer_review',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'product_price' => 'decimal:2',
            'quantity' => 'integer',
            'total_price' => 'decimal:2',
            'rating' => 'integer',
            'reviewed_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function generateOrderCode(Store $store): string
    {
        $prefix = 'ORD-' . strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $store->slug), 0, 4)) . '-' . date('ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

        return "{$prefix}-{$random}";
    }

    public function paymentProofUrl(): ?string
    {
        if (! $this->payment_proof_path) {
            return null;
        }

        if (str_starts_with($this->payment_proof_path, 'http://') || str_starts_with($this->payment_proof_path, 'https://')) {
            return $this->payment_proof_path;
        }

        return asset('storage/' . ltrim($this->payment_proof_path, '/'));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Verifikasi',
            'paid' => 'Sudah Dibayar',
            'processing' => 'Sedang Diproses',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'badge-warning',
            'paid' => 'badge-success',
            'processing' => 'badge-bundle',
            'completed' => 'badge-primary',
            'cancelled' => 'badge-critical',
            default => 'badge-secondary',
        };
    }

    public function isReviewed(): bool
    {
        return ! empty($this->buyer_review) || ! empty($this->rating);
    }

    public function stars(): string
    {
        $rating = max(1, min(5, (int) ($this->rating ?? 5)));
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }

    public function displayProductImageUrl(): string
    {
        if ($this->product && method_exists($this->product, 'imageUrl')) {
            return $this->product->imageUrl();
        }

        $firstItem = $this->items->first();
        if ($firstItem && $firstItem->product && method_exists($firstItem->product, 'imageUrl')) {
            return $firstItem->product->imageUrl();
        }

        return 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80';
    }

    public function buyerMaskedName(): string
    {
        if (empty($this->buyer_name)) {
            return 'Pembeli';
        }

        $parts = explode(' ', trim($this->buyer_name));
        if (count($parts) === 1) {
            return $parts[0];
        }

        return $parts[0] . ' ' . strtoupper(substr($parts[1], 0, 1)) . '.';
    }
}

