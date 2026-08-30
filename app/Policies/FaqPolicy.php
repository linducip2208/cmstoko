<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class FaqPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'faqs';
    }
}
