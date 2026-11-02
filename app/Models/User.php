<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryStores(): BelongsToMany
    {
        return $this->stores()->wherePivot('is_primary', true);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function customerProfiles(): HasMany
    {
        return $this->hasMany(StoreCustomerProfile::class);
    }

    public function customerProfileFor(int $storeId): ?StoreCustomerProfile
    {
        return $this->customerProfiles()->where('store_id', $storeId)->first();
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function managesStore(int $storeId): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        return $this->stores()->where('stores.id', $storeId)->exists();
    }

    public function isPrimaryOf(int $storeId): bool
    {
        return $this->stores()
            ->where('stores.id', $storeId)
            ->wherePivot('is_primary', true)
            ->exists();
    }

    public function avatarUrl(): string
    {
        $name = urlencode($this->name ?: 'User');
        return "https://ui-avatars.com/api/?name={$name}&background=0f172a&color=ffffff&size=128&bold=true";
    }

    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name ?: 'U', 0, 1));
    }
}
