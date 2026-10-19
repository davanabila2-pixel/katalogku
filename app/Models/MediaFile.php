<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaFile extends Model
{
    protected $fillable = [
        'store_id',
        'disk',
        'provider',
        'file_id',
        'file_name',
        'mime_type',
        'file_size',
        'public_url',
        'metadata',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'file_size' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): ?string
    {
        return $this->public_url ?: null;
    }
}
