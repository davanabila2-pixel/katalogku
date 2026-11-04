<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndonesiaCity extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'province_id',
        'name',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(IndonesiaProvince::class, 'province_id', 'id');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(IndonesiaDistrict::class, 'city_id', 'id')->orderBy('name', 'asc');
    }
}

