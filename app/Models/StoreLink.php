<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreLink extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'title',
        'url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Alamat aman untuk ditautkan. Validasi sudah mewajibkan http/https,
     * method ini jaring pengaman kedua kalau ada data lama yang bermasalah.
     */
    public function safeUrl(): string
    {
        $url = trim($this->url);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : 'https://'.$url;
    }
}
