<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class CustomerGroupPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'customers';
    }
}
