<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndonesiaDistrict extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'city_id',
        'name',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(IndonesiaCity::class, 'city_id', 'id');
    }
}

