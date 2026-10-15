<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->managesStore($product->store_id);
    }

    public function create(User $user, ?int $storeId = null): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $storeId !== null && $user->managesStore($storeId);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->managesStore($product->store_id);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->managesStore($product->store_id);
    }
}
