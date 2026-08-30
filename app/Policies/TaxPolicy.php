<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class TaxPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'settings';
    }
}
