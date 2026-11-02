<?php

namespace App\Policies;

use App\Models\StoreLink;
use App\Models\User;

class StoreLinkPolicy
{
    public function viewAny(User $user, ?int $storeId = null): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $storeId !== null && $user->managesStore($storeId);
    }

    public function view(User $user, StoreLink $link): bool
    {
        return $user->managesStore($link->store_id);
    }

    public function create(User $user, ?int $storeId = null): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $storeId !== null && $user->managesStore($storeId);
    }

    public function update(User $user, StoreLink $link): bool
    {
        return $user->managesStore($link->store_id);
    }

    public function delete(User $user, StoreLink $link): bool
    {
        return $user->managesStore($link->store_id);
    }
}
