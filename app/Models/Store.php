<?php

namespace App\Models;

use App\Services\SlugService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_path',
        'qr_image_path',
        'qr_account_name',
        'contact_information',
        'address',
        'status',
        'hide_component_products',
    ];

    protected function casts(): array
    {
        return [
            'hide_component_products' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Store $store) {
            if (empty($store->slug)) {
                $store->slug = SlugService::uniqueStoreSlug($store->name);
            }
        });

        static::updating(function (Store $store) {
            if ($store->isDirty('slug') && $store->exists) {
                StoreSlugHistory::create([
                    'store_id' => $store->id,
                    'old_slug' => $store->getOriginal('slug'),
                    'new_slug' => $store->slug,
                ]);
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryAdmin(): ?User
    {
        return $this->users()->wherePivot('is_primary', true)->first();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseBatches(): HasMany
    {
        return $this->hasMany(PurchaseBatch::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function mediaFiles(): HasMany
    {
        return $this->hasMany(MediaFile::class);
    }

    public function slugHistories(): HasMany
    {
        return $this->hasMany(StoreSlugHistory::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function url(): string
    {
        return url('/'.$this->slug);
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }

        return null;
    }

    public function qrImageUrl(): ?string
    {
        if (! $this->qr_image_path) {
            return null;
        }

        if (str_starts_with($this->qr_image_path, 'http://') || str_starts_with($this->qr_image_path, 'https://')) {
            return $this->qr_image_path;
        }

        return null;
    }

    public function hasStaticQr(): bool
    {
        return ! empty($this->qr_image_path);
    }

    public function cleanPhoneNumber(): ?string
    {
        if (empty($this->contact_information)) {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', $this->contact_information);
        if (str_starts_with($clean, '0')) {
            $clean = '62'.substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62'.$clean;
        }

        return strlen($clean) >= 9 ? $clean : null;
    }

    public function whatsappUrl(?string $message = null): ?string
    {
        $phone = $this->cleanPhoneNumber();
        if (! $phone) {
            return null;
        }

        if (empty($message)) {
            return "https://wa.me/{$phone}";
        }

        return "https://wa.me/{$phone}?text=".urlencode($message);
    }
}
