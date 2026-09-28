<?php

namespace App\Policies;

use App\Models\Buyer;
use App\Models\User;

/**
 * Buyers, their prices and their rules are the admin's business alone.
 */
class BuyerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Buyer $buyer): bool
    {
        return $user->isAdmin();
    }
}
