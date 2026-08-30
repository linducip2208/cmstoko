<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class CartRulePolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'promotions';
    }
}
