<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndonesiaProvince extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(IndonesiaCity::class, 'province_id', 'id')->orderBy('name', 'asc');
    }
}

