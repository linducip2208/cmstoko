<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByPermission;

class RolePolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'roles';
    }

    public static function update(User $user, Role $record): bool
    {
        if ($record->slug === Role::SUPER_ADMIN && ! $user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('roles.update');
    }

    public static function delete(User $user, Role $record): bool
    {
        if ($record->is_system) {
            return false;
        }

        return $user->hasPermission('roles.delete');
    }

    public static function deleteAny(User $user): bool
    {
        return $user->hasPermission('roles.delete');
    }
}
