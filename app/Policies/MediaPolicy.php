<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class MediaPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'media';
    }
}
