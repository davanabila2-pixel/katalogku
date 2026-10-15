<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSlugHistory extends Model
{
    protected $fillable = [
        'store_id',
        'old_slug',
        'new_slug',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
