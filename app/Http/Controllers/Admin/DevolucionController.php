<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DevolucionRequest;
use App\Models\Venta;
use App\Services\DevolucionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Endpoint de devoluciones desde el detalle de venta (HU-5.14, 5.15.4).
 */
class DevolucionController extends Controller
{
    public function store(
        DevolucionRequest $request,
        Venta $venta,
        DevolucionService $devoluciones
    ): JsonResponse|RedirectResponse {
        $datos = $request->validated();

        try {
            $devolucion = $devoluciones->registrar(
                $venta->id,
                $datos['items'],
                $datos['motivo'],
                strtolower($datos['operacion_id']),
                $request->user()->id,
            );
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getUserMessage(),
                    'code' => $e->getErrorCode(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getUserMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Devolución #{$devolucion->id} registrada.",
                'devolucion' => [
                    'id' => $devolucion->id,
                    'total_devuelto' => (float) $devolucion->total_devuelto,
                    'estado' => $devolucion->estado,
                ],
                'venta_estado' => $venta->fresh()->estado,
            ], 201);
        }

        return redirect()
            ->route('admin.ventas.show', $venta)
            ->with('success', "Devolución #{$devolucion->id} registrada.");
    }
}
