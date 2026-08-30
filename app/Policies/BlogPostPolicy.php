<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class BlogPostPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'blog';
    }
}
