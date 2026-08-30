<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByPermission;

class AuditLogPolicy
{
    use AuthorizesByPermission;

    protected static function permissionPrefix(): string
    {
        return 'audit-logs';
    }
}
