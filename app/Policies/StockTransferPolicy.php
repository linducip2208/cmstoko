<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class StockTransferPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'inventory';
    }
}
