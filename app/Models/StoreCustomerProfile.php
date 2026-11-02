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
        'notes',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

