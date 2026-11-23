<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Custom-CSS pagination links instead of the Tailwind default.
        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');

        // Password policy for registration, reset and profile updates.
        // (No uncompromised() check: it needs an internet connection,
        // which would break registration during offline demos.)
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->numbers();
        });
    }
}
