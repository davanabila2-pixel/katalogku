<?php

namespace App\Models;

use App\Services\HppService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'selling_price',
        'stock',
        'image_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function batchItems(): HasMany
    {
        return $this->hasMany(PurchaseBatchItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === 'active';
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }

    /** HPP per unit (rata-rata tertimbang). Lihat HppService. */
    public function hpp(): ?float
    {
        return app(HppService::class)->hppForProduct($this);
    }

    public function estimatedMargin(): ?float
    {
        return app(HppService::class)->marginForProduct($this);
    }

    public function imageUrl(): string
    {
        if ($this->image_path && str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        if ($this->image_path) {
            return asset('storage/'.$this->image_path);
        }

        return asset('images/product-fallback.svg');
    }
}
