<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class CouponPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'coupons';
    }
}
