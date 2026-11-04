<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\FusionarFavoritosAlLogin;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Models\AlertaStock;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
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
        // Admin es super-admin: tiene todos los permisos excepto reglas de negocio sobre usuarios
        Gate::before(function ($user, $ability, array $arguments = []) {
            if (isset($arguments[0]) && ($arguments[0] instanceof \App\Models\User || $arguments[0] === \App\Models\User::class)) {
                return null;
            }

            if ($user->hasRole('Admin')) {
                return true;
            }
        });

        // HU-5.11: 5 intentos por minuto para autorizar descuentos. La clave es
        // el vendedor autenticado (la IP solo si no hay sesión).
        RateLimiter::for('autorizar-descuento', function (Request $request) {
            return Limit::perMinute(5)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        // Paginación con Tailwind
        Paginator::useTailwind();

        Gate::policy(\App\Models\Producto::class, \App\Policies\ProductoPolicy::class);
        Gate::policy(AlertaStock::class, \App\Policies\AlertaStockPolicy::class);
        Gate::policy(\App\Models\Ubicacion::class, \App\Policies\UbicacionPolicy::class);
        Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\App\Models\Proveedor::class, \App\Policies\ProveedorPolicy::class);
        Gate::policy(\App\Models\Cliente::class, \App\Policies\ClientePolicy::class);
        Gate::policy(\App\Models\OrdenCompra::class, \App\Policies\OrdenCompraPolicy::class);

        // Auditoría de cambios CRUD sobre usuarios (tarea 1.1.15)
        User::observe(UserObserver::class);

        // Contador de alertas sin leer para el badge del header.
        View::composer('layouts.app', function ($view) {
            $view->with('alertasNoLeidasCount', auth()->check()
                ? AlertaStock::query()->noLeidas()->count()
                : 0);
        });

        // Auditoría de autenticación
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Login::class, FusionarFavoritosAlLogin::class);
        Event::listen(Failed::class, LogFailedLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);
    }
}
