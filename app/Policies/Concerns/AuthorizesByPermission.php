<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait AuthorizesByPermission
{
    abstract protected static function permissionPrefix(): string;

    public static function viewAny(User $user): bool
    {
        return $user->hasPermission(static::permissionPrefix().'.view');
    }

    public static function view(User $user, $record): bool
    {
        return $user->hasPermission(static::permissionPrefix().'.view');
    }

    public static function create(User $user): bool
    {
        return $user->hasPermission(static::permissionPrefix().'.create');
    }

    public static function update(User $user, $record): bool
    {
        return $user->hasPermission(static::permissionPrefix().'.update');
    }

    public static function delete(User $user, $record): bool
    {
        return $user->hasPermission(static::permissionPrefix().'.delete');
    }

    public static function deleteAny(User $user): bool
    {
        return $user->hasPermission(static::permissionPrefix().'.delete');
    }

    public static function restore(User $user, $record): bool
    {
        return $user->hasPermission(static::permissionPrefix().'.delete');
    }

    public static function forceDelete(User $user, $record): bool
    {
        return $user->hasPermission(static::permissionPrefix().'.delete');
    }

    public static function reorder(User $user): bool
    {
        return $user->hasPermission(static::permissionPrefix().'.update');
    }
}
