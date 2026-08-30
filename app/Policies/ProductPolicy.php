<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class ProductPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'products';
    }
}
