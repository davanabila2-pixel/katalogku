<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCustomerProfile extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'name',
        'phone',
        'address',
        'postal_code',
        'latitude',
        'longitude',
        'city',
        'district',
        'province',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function hasCoordinates(): bool
    {
        return ! is_null($this->latitude) && ! is_null($this->longitude);
    }

    public function destinationForBiteship(): array
    {
        return [
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addresses()
    {
        return StoreCustomerAddress::where('store_id', $this->store_id)
            ->where('user_id', $this->user_id)
            ->orderByDesc('is_default')
            ->latest();
    }

    public function defaultAddress(): ?StoreCustomerAddress
    {
        return $this->addresses()->where('is_default', true)->first()
            ?: $this->addresses()->first();
    }

    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name ?: 'P', 0, 1));
    }

    public function avatarUrl(): string
    {
        $name = urlencode($this->name ?: 'Pelanggan');
        return "https://ui-avatars.com/api/?name={$name}&background=0f172a&color=ffffff&size=128&bold=true";
    }
}

