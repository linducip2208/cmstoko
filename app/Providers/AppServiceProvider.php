<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPermissionGates();

        \App\Models\Role::observe(\App\Observers\RoleObserver::class);
        \App\Models\Category::observe(\App\Observers\CategoryObserver::class);
    }

    protected function registerPermissionGates(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        $resources = (array) config('permissions.resources', []);

        foreach ($resources as $resource => $actions) {
            foreach ((array) $actions as $action) {
                $slug = $resource.'.'.$action;

                Gate::define($slug, function (User $user) use ($slug) {
                    return $user->hasPermission($slug);
                });
            }
        }
    }
}
