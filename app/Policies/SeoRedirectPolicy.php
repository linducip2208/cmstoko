<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class SeoRedirectPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'redirects';
    }
}
