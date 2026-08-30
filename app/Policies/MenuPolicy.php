<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class MenuPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'menus';
    }
}
