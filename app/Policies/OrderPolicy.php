<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->canAccessBranch($order->branch_id) &&
            ($user->role !== Role::Cashier || $order->cashier_id === $user->id);
    }
}
