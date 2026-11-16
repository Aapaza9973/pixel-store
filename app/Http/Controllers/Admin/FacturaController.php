<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\FacturaElectronica;
use App\Models\Venta;
use App\Services\FacturacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Facturación electrónica (HU-4.1). Permisos: ver / emitir / anular facturacion.
 */
class FacturaController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('ver facturacion'), 403);

        $estado = $request->string('estado')->toString();

        $facturas = FacturaElectronica::query()
            ->with(['venta.cliente:id,nombre', 'user:id,name'])
            ->when(
                in_array($estado, FacturaElectronica::ESTADOS, true),
                fn ($query) => $query->where('estado', $estado)
            )
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.facturacion.index', [
            'facturas' => $facturas,
            'estado' => $estado,
            'estados' => FacturaElectronica::ESTADOS,
        ]);
    }

    public function store(Request $request, FacturacionService $facturacion): RedirectResponse
    {
        abort_unless($request->user()?->can('emitir facturacion'), 403);

        $datos = $request->validate([
            'venta_id' => ['required', 'integer', 'exists:ventas,id'],
        ], [
            'venta_id.required' => 'Indique la venta a facturar.',
            'venta_id.exists' => 'La venta indicada no existe.',
        ]);

        $venta = Venta::findOrFail((int) $datos['venta_id']);

        try {
            $factura = $facturacion->emitir($venta, $request->user()->id);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getUserMessage());
        }

        return redirect()
            ->route('admin.facturacion.show', $factura)
            ->with('success', 'Factura '.$factura->numero_factura.' emitida.');
    }

    public function show(Request $request, FacturaElectronica $factura): View
    {
        abort_unless($request->user()?->can('ver facturacion'), 403);

        $factura->load(['venta.cliente', 'venta.detalles.producto', 'user:id,name']);

        return view('admin.facturacion.show', compact('factura'));
    }

    public function anular(Request $request, FacturaElectronica $factura, FacturacionService $facturacion): RedirectResponse
    {
        abort_unless($request->user()?->can('anular facturacion'), 403);

        $datos = $request->validate([
            'motivo' => ['required', Rule::in(['Error de datos', 'Devolución total', 'Duplicada', 'Otro'])],
        ], [
            'motivo.required' => 'Indique el motivo de la anulación.',
            'motivo.in' => 'El motivo seleccionado no es válido.',
        ]);

        try {
            $facturacion->anular($factura, $datos['motivo']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getUserMessage());
        }

        return redirect()
            ->route('admin.facturacion.show', $factura)
            ->with('success', 'Factura anulada.');
    }
}
