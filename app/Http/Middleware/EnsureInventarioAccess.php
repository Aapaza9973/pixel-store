<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureInventarioAccess
{
    /**
     * Restringe el acceso al módulo de inventario al permiso `ver inventario`.
     * Admin pasa automáticamente vía `Gate::before`.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || ! Auth::user()->can('ver inventario')) {
            abort(403, 'No tiene permisos para acceder al módulo de inventario.');
        }

        return $next($request);
    }
}
