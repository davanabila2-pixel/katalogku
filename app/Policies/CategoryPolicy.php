<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Category $category): bool
    {
        return $user->managesStore($category->store_id);
    }

    public function create(User $user, ?int $storeId = null): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $storeId !== null && $user->managesStore($storeId);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->managesStore($category->store_id);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->managesStore($category->store_id);
    }
}
