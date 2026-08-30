<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesByPermission;

class UserPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'users';
    }

    public static function update(User $user, User $record): bool
    {
        if ($user->hasPermission('users.update')) {
            // Only Super Admin / users with roles.update may edit super admins or roles.
            if ($record->isSuperAdmin() && ! $user->hasPermission('roles.update')) {
                return false;
            }

            return true;
        }

        return false;
    }

    public static function delete(User $user, User $record): bool
    {
        if ($record->is($user) || $record->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('users.delete');
    }

    public static function deleteAny(User $user): bool
    {
        return $user->hasPermission('users.delete');
    }
}
