<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // System admins bypass all capability checks.
        Gate::before(function ($user, $ability) {
            return $user->isAdmin() ? true : null;
        });

        // Generic capability gate: Gate::allows('cap:<capability>') or @can('cap:x').
        Gate::define('cap', fn ($user, string $capability) => $user->hasCapability($capability));

        \App\Models\Request::observe(\App\Observers\RequestObserver::class);
    }
}
