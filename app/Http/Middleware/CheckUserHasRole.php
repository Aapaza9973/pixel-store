<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\LogAuditoria;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserHasRole
{
    /**
     * Verifica que el usuario autenticado tenga al menos un rol asignado.
     * Si no lo tiene, deniega el acceso (403) y registra el intento en auditoría.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->roles()->count() === 0) {
            try {
                LogAuditoria::create([
                    'user_id' => Auth::id(),
                    'accion' => 'acceso_denegado_sin_rol',
                    'modelo' => null,
                    'modelo_id' => null,
                    'datos_anteriores' => null,
                    'datos_nuevos' => [
                        'ruta_intentada' => $request->path(),
                        'ip' => $request->ip(),
                    ],
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }

            abort(403, 'No tiene permisos asignados.');
        }

        return $next($request);
    }
}
