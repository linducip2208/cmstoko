<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class CategoryPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'categories';
    }
}
