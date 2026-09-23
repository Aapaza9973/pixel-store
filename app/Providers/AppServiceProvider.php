<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Admin es super-admin: tiene todos los permisos
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Admin')) {
                return true;
            }
        });

        // Paginación con Tailwind
        Paginator::useTailwind();

        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(15, 5)->by($request->input('email').'|'.$request->ip())
                ->response(function () {
                    return back()->withErrors([
                        'email' => 'Demasiados intentos fallidos. Por favor, inténtelo de nuevo más tarde.',
                    ]);
                });
        });
    }
}
