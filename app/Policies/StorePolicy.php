<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function view(User $user, Store $store): bool
    {
        return $user->managesStore($store->id);
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Store $store): bool
    {
        return $user->managesStore($store->id);
    }

    public function updateSlug(User $user, Store $store): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isPrimaryOf($store->id);
    }

    public function delete(User $user, Store $store): bool
    {
        return $user->isSuperadmin();
    }
}
