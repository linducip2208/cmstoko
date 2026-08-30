<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class FlashSalePolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'flash-sales';
    }
}
