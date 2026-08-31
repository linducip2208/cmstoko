<?php

namespace App\Providers;

use App\Contracts\SearchEngine;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use App\Observers\CategoryObserver;
use App\Observers\RoleObserver;
use App\Services\DatabaseSearchEngine;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Search abstraction: swap for Meilisearch/Typesense driver later.
        $this->app->bind(SearchEngine::class, DatabaseSearchEngine::class);
    }

    public function boot(): void
    {
        $this->registerPermissionGates();

        Role::observe(RoleObserver::class);
        Category::observe(CategoryObserver::class);
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
