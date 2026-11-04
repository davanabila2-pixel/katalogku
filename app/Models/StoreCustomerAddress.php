<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'address',
        'postal_code',
        'city',
        'district',
        'province',
        'latitude',
        'longitude',
        'notes',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_default' => 'boolean',
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
}

