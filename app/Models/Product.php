<?php

namespace App\Models;

use App\Services\HppService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'category_id',
        'is_bundle',
        'is_hidden_from_catalog',
        'name',
        'slug',
        'sku',
        'barcode',
        'barcode_image_path',
        'description',
        'selling_price',
        'weight',
        'stock',
        'image_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_bundle' => 'boolean',
            'is_hidden_from_catalog' => 'boolean',
            'selling_price' => 'decimal:2',
            'weight' => 'integer',
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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    public function bundleItems(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_bundles', 'bundle_id', 'item_product_id')
            ->using(ProductBundle::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function partOfBundles(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_bundles', 'item_product_id', 'bundle_id')
            ->using(ProductBundle::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function isBundle(): bool
    {
        return (bool) $this->is_bundle;
    }

    public function calculateBundleStock(): int
    {
        if (! $this->isBundle()) {
            return (int) ($this->attributes['stock'] ?? 0);
        }

        $items = $this->bundleItems;
        if ($items->isEmpty()) {
            return 0;
        }

        $possibleSets = [];
        foreach ($items as $item) {
            if ($item->status !== 'active') {
                return 0;
            }

            $needed = max(1, (int) ($item->pivot->quantity ?? 1));
            $available = max(0, (int) $item->stock);
            $possibleSets[] = intdiv($available, $needed);
        }

        return ! empty($possibleSets) ? min($possibleSets) : 0;
    }

    public function getStockAttribute($value): int
    {
        if ($this->isBundle()) {
            return $this->calculateBundleStock();
        }

        return (int) $value;
    }

    public function syncBundleStock(): void
    {
        if ($this->isBundle()) {
            $this->unsetRelation('bundleItems');
            $calculated = $this->calculateBundleStock();
            $this->updateQuietly(['stock' => $calculated]);
        }
    }

    public function bundleOriginalPrice(): float
    {
        if (! $this->isBundle()) {
            return (float) $this->selling_price;
        }

        return (float) $this->bundleItems->sum(function ($item) {
            return (float) $item->selling_price * (int) ($item->pivot->quantity ?? 1);
        });
    }

    public function bundleSavings(): float
    {
        $original = $this->bundleOriginalPrice();
        $selling = (float) $this->selling_price;

        return max(0.0, round($original - $selling, 2));
    }

    /**
     * Total berat paket setelan (bundle) dalam satuan gram, dihitung dari
     * akumulasi berat komponen dikalikan kuantitas masing-masing per paket.
     */
    public function bundleTotalWeight(): int
    {
        if (! $this->isBundle()) {
            return max(1, (int) ($this->attributes['weight'] ?? 500));
        }

        $items = $this->bundleItems;
        if ($items->isEmpty()) {
            return max(1, (int) ($this->attributes['weight'] ?? 500));
        }

        $total = 0;
        foreach ($items as $item) {
            $itemWeight = max(1, (int) ($item->weight ?? 500));
            $qty = max(1, (int) ($item->pivot->quantity ?? 1));
            $total += ($itemWeight * $qty);
        }

        return max(1, $total);
    }

    /**
     * Berat efektif produk dalam satuan gram yang dipakai untuk perhitungan ongkir.
     * Jika produk setelan (bundle): menggunakan berat khusus jika diisi admin (override box),
     * atau otomatis menggunakan akumulasi berat komponen penyusunnya.
     */
    public function effectiveWeight(): int
    {
        if ($this->isBundle()) {
            $customWeight = (int) ($this->attributes['weight'] ?? 0);
            if ($customWeight > 0) {
                return $customWeight;
            }

            return $this->bundleTotalWeight();
        }

        return max(1, (int) ($this->attributes['weight'] ?? 500));
    }

    /**
     * Format angka gram ke tampilan berat ramah pengguna (cth: "500 gr", "1,2 kg", "2 kg").
     */
    public static function formatGrams(int $grams): string
    {
        if ($grams >= 1000) {
            $kg = round($grams / 1000, 2);
            $formatted = rtrim(rtrim(number_format($kg, 2, ',', '.'), '0'), ',');

            return "{$formatted} kg";
        }

        return number_format($grams, 0, ',', '.') . ' gr';
    }

    /**
     * Format tampilan berat ramah pengguna produk ini.
     */
    public function formattedWeight(): string
    {
        return static::formatGrams($this->effectiveWeight());
    }

    public function isHiddenFromCatalog(): bool
    {
        return (bool) $this->is_hidden_from_catalog;
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where('status', 'active')
            ->where('is_hidden_from_catalog', false);
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
        return $this->status === 'active' && ! $this->is_hidden_from_catalog;
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

        return asset('images/product-fallback.svg');
    }

    /**
     * Dapatkan seluruh daftar URL foto galeri produk (foto utama + foto tambahan).
     */
    public function galleryUrls(): array
    {
        $urls = [];

        if ($this->image_path && str_starts_with($this->image_path, 'http')) {
            $urls[] = $this->image_path;
        }

        // Muat relasi images jika belum dimuat
        $extraImages = $this->relationLoaded('images') ? $this->images : $this->images()->get();
        foreach ($extraImages as $img) {
            $u = $img->imageUrl();
            if ($u && ! in_array($u, $urls, true) && ! str_contains($u, 'fallback')) {
                $urls[] = $u;
            }
        }

        if (empty($urls)) {
            $urls[] = asset('images/product-fallback.svg');
        }

        return $urls;
    }

    public function hasMultipleImages(): bool
    {
        return count($this->galleryUrls()) > 1;
    }

    public function barcodeUrl(): ?string
    {
        if ($this->barcode_image_path && str_starts_with($this->barcode_image_path, 'http')) {
            return $this->barcode_image_path;
        }

        return null;
    }

    public function scanUrl(): string
    {
        return route('product.scan', $this->barcode ?? $this->id);
    }

    public function qrCodeDataUri(): string
    {
        return app(\App\Services\BarcodeService::class)->generateQrCodeDataUri($this);
    }

    public function url(?Store $store = null): string
    {
        $targetStore = $store ?? $this->store;
        if (! $targetStore) {
            return url('/');
        }

        return route('store.product', [$targetStore->slug, $this->slug]);
    }

    public function buildWhatsAppMessage(?Store $store = null, ?string $userQuery = null): string
    {
        $targetStore = $store ?? $this->store;
        $storeName = $targetStore ? $targetStore->name : 'Toko';
        $price = 'Rp '.number_format((float) $this->selling_price, 0, ',', '.');
        $desc = $this->description ? trim(strip_tags($this->description)) : '-';
        if (mb_strlen($desc) > 150) {
            $desc = mb_substr($desc, 0, 147).'...';
        }
        $url = $this->url($targetStore);
        $query = $userQuery ?: 'Apakah produk ini masih tersedia?';

        return "Halo {$storeName}, saya tertarik dengan produk ini:\n\n"
            ."📦 *{$this->name}*\n"
            ."💰 *Harga*: {$price}\n"
            ."📝 *Deskripsi*: {$desc}\n"
            ."🔗 *Link*: {$url}\n\n"
            ."{$query}";
    }

    public function whatsappUrl(?Store $store = null, ?string $userQuery = null): ?string
    {
        $targetStore = $store ?? $this->store;
        if (! $targetStore) {
            return null;
        }

        $message = $this->buildWhatsAppMessage($targetStore, $userQuery);

        return $targetStore->whatsappUrl($message);
    }
}
