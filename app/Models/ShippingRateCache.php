<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRateCache extends Model
{
    use HasFactory;

    protected $fillable = [
        'cache_key',
        'origin_postal',
        'destination_postal',
        'origin_city',
        'destination_city',
        'origin_district',
        'destination_district',
        'origin_lat',
        'origin_lng',
        'destination_lat',
        'destination_lng',
        'couriers',
        'weight_grams',
        'rates',
        'origin_meta',
        'destination_meta',
        'hit_count',
        'last_synced_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'origin_lat' => 'float',
            'origin_lng' => 'float',
            'destination_lat' => 'float',
            'destination_lng' => 'float',
            'weight_grams' => 'integer',
            'hit_count' => 'integer',
            'rates' => 'array',
            'origin_meta' => 'array',
            'destination_meta' => 'array',
            'last_synced_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Scope untuk mengambil cache tarif yang masih berlaku (belum kedaluwarsa).
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Cek apakah cache sudah kedaluwarsa (> 1 hari / lewat masa berlaku).
     */
    public function isExpired(): bool
    {
        return $this->expires_at ? $this->expires_at->isPast() : true;
    }

    /**
     * Catat hit cache agar terpantau berapa kali panggilan API Biteship telah dihemat.
     */
    public function recordHit(): void
    {
        $this->increment('hit_count');
    }

    /**
     * Hapus semua cache yang telah kedaluwarsa.
     */
    public static function pruneExpired(): int
    {
        return static::where('expires_at', '<=', now())->delete();
    }
}
