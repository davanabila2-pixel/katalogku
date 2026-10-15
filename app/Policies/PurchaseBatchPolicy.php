<?php

namespace App\Policies;

use App\Models\PurchaseBatch;
use App\Models\User;

class PurchaseBatchPolicy
{
    public function view(User $user, PurchaseBatch $batch): bool
    {
        return $user->managesStore($batch->store_id);
    }

    public function create(User $user, ?int $storeId = null): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $storeId !== null && $user->managesStore($storeId);
    }

    public function cancel(User $user, PurchaseBatch $batch): bool
    {
        return $user->managesStore($batch->store_id) && ! $batch->isCancelled();
    }
}
