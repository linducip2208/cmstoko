<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public static function viewAny(User $user): bool
    {
        return $user->hasPermission('orders.view');
    }

    public static function view(User $user, Order $record): bool
    {
        return $user->hasPermission('orders.view');
    }

    public static function update(User $user, Order $record): bool
    {
        return $user->hasPermission('orders.update');
    }

    public static function cancel(User $user, Order $record): bool
    {
        return $user->hasPermission('orders.cancel') && $record->canTransitionTo(Order::STATUS_CANCELLED);
    }
}
