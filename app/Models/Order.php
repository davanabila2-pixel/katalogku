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
        'shipping_courier',
        'shipping_service',
        'shipping_fee',
        'shipping_etd',
        'tracking_number',
        'shipped_at',
        'buyer_postal_code',
        'buyer_latitude',
        'buyer_longitude',
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
            'shipping_fee' => 'decimal:2',
            'buyer_latitude' => 'float',
            'buyer_longitude' => 'float',
            'shipped_at' => 'datetime',
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

    public function buyerWhatsappUrl(?string $message = null): ?string
    {
        if (empty($this->buyer_phone)) {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', $this->buyer_phone);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }

        if (strlen($clean) < 9) {
            return null;
        }

        return empty($message)
            ? "https://wa.me/{$clean}"
            : "https://wa.me/{$clean}?text=" . urlencode($message);
    }

    /**
     * Label dan class badge untuk tampilan halaman Riwayat Pesanan pelanggan.
     *
     * @return array{label: string, class: string}
     */
    public function customerStatusMeta(): array
    {
        return match ($this->status) {
            'pending' => ['label' => 'Menunggu Konfirmasi', 'class' => 'status-badge-pending'],
            'paid' => ['label' => 'Sudah Dibayar', 'class' => 'status-badge-completed'],
            'processing' => ['label' => ! empty($this->tracking_number) ? 'Dikirim / Diproses' : 'Diproses', 'class' => 'status-badge-processing'],
            'shipped' => ['label' => 'Dikirim', 'class' => 'status-badge-shipped'],
            'completed' => ['label' => 'Selesai', 'class' => 'status-badge-completed'],
            'cancelled' => ['label' => 'Dibatalkan', 'class' => 'status-badge-cancelled'],
            default => ['label' => ucfirst((string) $this->status), 'class' => 'status-badge-pending'],
        };
    }
}

