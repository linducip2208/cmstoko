<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class NewsletterSubscriberPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'newsletter';
    }
}
