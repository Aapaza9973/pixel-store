<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DevolucionRequest;
use App\Models\Devolucion;
use App\Models\Venta;
use App\Services\DevolucionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    /**
     * Bandeja de solicitudes de devolución (HU-2.4b).
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('ver devoluciones'), 403);

        $estado = $request->string('estado')->toString();

        $devoluciones = Devolucion::query()
            ->with(['venta.cliente:id,nombre', 'user:id,name', 'detalles'])
            ->when(
                in_array($estado, ['pendiente', 'procesada', 'anulada', 'rechazada'], true),
                fn ($query) => $query->where('estado', $estado)
            )
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.devoluciones.index', compact('devoluciones', 'estado'));
    }

    /**
     * Registra una devolución como solicitud pendiente, sin mover stock.
     */
    public function solicitar(
        DevolucionRequest $request,
        Venta $venta,
        DevolucionService $devoluciones
    ): JsonResponse|RedirectResponse {
        $datos = $request->validated();

        try {
            $devolucion = $devoluciones->solicitar(
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
                'message' => "Solicitud de devolución #{$devolucion->id} registrada.",
                'devolucion' => [
                    'id' => $devolucion->id,
                    'total_devuelto' => (float) $devolucion->total_devuelto,
                    'estado' => $devolucion->estado,
                ],
            ], 201);
        }

        return redirect()
            ->route('admin.devoluciones.index', ['estado' => 'pendiente'])
            ->with('success', "Solicitud de devolución #{$devolucion->id} registrada.");
    }

    public function aprobar(Request $request, Devolucion $devolucion, DevolucionService $devoluciones): RedirectResponse
    {
        abort_unless($request->user()?->can('aprobar devoluciones'), 403);

        try {
            $devoluciones->aprobar($devolucion, $request->user()->id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getUserMessage());
        }

        return back()->with('success', "Devolución #{$devolucion->id} aprobada.");
    }

    public function rechazar(Request $request, Devolucion $devolucion, DevolucionService $devoluciones): RedirectResponse
    {
        abort_unless($request->user()?->can('rechazar devoluciones'), 403);

        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:255'],
        ], [
            'motivo.required' => 'Indique el motivo del rechazo.',
        ]);

        try {
            $devoluciones->rechazar($devolucion, $datos['motivo'], $request->user()->id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getUserMessage());
        }

        return back()->with('success', "Devolución #{$devolucion->id} rechazada.");
    }
}
