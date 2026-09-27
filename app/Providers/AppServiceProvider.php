<?php

namespace App\Providers;

use App\Models\AlertaStock;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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

        Gate::policy(\App\Models\Producto::class, \App\Policies\ProductoPolicy::class);
        Gate::policy(AlertaStock::class, \App\Policies\AlertaStockPolicy::class);
        Gate::policy(\App\Models\Ubicacion::class, \App\Policies\UbicacionPolicy::class);

        // Contador de alertas sin leer para el badge del header.
        View::composer('layouts.app', function ($view) {
            $view->with('alertasNoLeidasCount', auth()->check()
                ? AlertaStock::query()->noLeidas()->count()
                : 0);
        });
    }
}
