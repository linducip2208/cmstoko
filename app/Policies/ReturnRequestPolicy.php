<?php

namespace App\Policies;

use App\Models\ReturnRequest;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByPermission;

class ReturnRequestPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'returns';
    }

    public static function viewAny(User $user): bool
    {
        return $user->hasPermission('returns.view');
    }

    public static function view(User $user, ReturnRequest $record): bool
    {
        return $user->hasPermission('returns.view');
    }

    public static function update(User $user, ReturnRequest $record): bool
    {
        return $user->hasPermission('returns.update');
    }
}
