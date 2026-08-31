<?php

namespace App\Observers;

use App\Models\Role;
use App\Support\Audit;

class RoleObserver
{
    public function created(Role $role): void
    {
        Audit::record('role.create', subject: $role, after: ['name' => $role->name, 'slug' => $role->slug]);
    }

    public function updated(Role $role): void
    {
        Audit::record('role.update', subject: $role, after: [
            'name' => $role->name,
            'slug' => $role->slug,
            'permissions' => $role->permissions()->pluck('slug')->all(),
        ]);
    }

    public function deleted(Role $role): void
    {
        Audit::record('role.delete', subject: $role, before: ['name' => $role->name, 'slug' => $role->slug]);
    }
}
