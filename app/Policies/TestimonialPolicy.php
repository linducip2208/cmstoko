<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class TestimonialPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'testimonials';
    }
}
