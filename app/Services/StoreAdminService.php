<?php

namespace App\Services;

use App\Models\Store;
use App\Models\User;

class StoreAdminService
{
    /**
     * Tunjuk admin utama toko berdasarkan email.
     * Akun admin dibuat otomatis bila belum ada; superadmin tidak dijadikan
     * admin toko (tetap punya akses lintas toko).
     */
    public function assignPrimaryAdmin(Store $store, string $email, ?string $name = null): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $name ?? str($email)->before('@')->toString(),
                'email' => $email,
                'password' => null,
                'role' => 'admin',
                'is_active' => true,
            ]);
        }

        if ($user->isSuperadmin()) {
            return;
        }

        $store->users()->syncWithoutDetaching([$user->id => ['is_primary' => false]]);

        $store->users()->updateExistingPivot(
            $store->users()->wherePivot('is_primary', true)->where('users.id', '!=', $user->id)->pluck('users.id')->toArray(),
            ['is_primary' => false]
        );
        $store->users()->updateExistingPivot($user->id, ['is_primary' => true]);
    }
}
