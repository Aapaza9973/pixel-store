<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\LogAuditoria;
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
        if (! Auth::check() || ! Auth::user()->puedeAccederAlInventario()) {
            $this->registrarAccesoDenegado($request);

            abort(403, 'No tiene permisos para acceder al módulo de inventario.');
        }

        return $next($request);
    }

    /**
     * Audita el intento de acceso denegado. Best-effort: un fallo al escribir
     * en `logs_auditoria` nunca debe cambiar la respuesta al usuario.
     */
    private function registrarAccesoDenegado(Request $request): void
    {
        try {
            LogAuditoria::create([
                'user_id' => Auth::id(),
                'accion' => 'acceso_denegado_inventario',
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
    }
}
