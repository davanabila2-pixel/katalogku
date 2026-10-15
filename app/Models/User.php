<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
}
