<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('permissions');

        foreach ($config['roles'] as $slug => $name) {
            Role::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'is_system' => true,
                'is_staff' => $slug !== Role::CUSTOMER,
                'sort_order' => array_key_first($config['roles']) === $slug ? 0 : array_search($slug, array_keys($config['roles']), true),
            ]);
        }

        foreach ($config['resources'] as $resource => $actions) {
            $label = ucwords(str_replace('-', ' ', $resource));

            foreach ((array) $actions as $action) {
                Permission::updateOrCreate(['slug' => $resource.'.'.$action], [
                    'name' => $label.' — '.ucfirst($action),
                    'group' => $resource,
                ]);
            }
        }

        $permissionIds = Permission::pluck('id', 'slug');

        foreach ($config['role_grants'] as $roleSlug => $grants) {
            $role = Role::where('slug', $roleSlug)->first();

            if (! $role) {
                continue;
            }

            if ($grants === '*') {
                $role->permissions()->sync($permissionIds->values());

                continue;
            }

            $slugs = [];
            foreach ($grants as $grant) {
                if (is_string($grant)) {
                    foreach ((array) ($config['resources'][$grant] ?? []) as $action) {
                        $slugs[] = $grant.'.'.$action;
                    }
                } else {
                    foreach ($grant as $resource => $actions) {
                        foreach ((array) $actions as $action) {
                            $slugs[] = $resource.'.'.$action;
                        }
                    }
                }
            }

            $role->permissions()->sync(
                collect($slugs)->map(fn (string $slug) => $permissionIds[$slug] ?? null)->filter()->values()->all()
            );
        }
    }
}
