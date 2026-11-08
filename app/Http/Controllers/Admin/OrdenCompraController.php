<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrdenCompraRequest;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\OrdenCompraService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Órdenes de compra: alta, detalle, recepción (3.4.9-3.4.10) y PDF (3.4.11-3.4.12).
 */
class OrdenCompraController extends Controller
{
    public function __construct(private readonly OrdenCompraService $ordenes)
    {
        $this->authorizeResource(OrdenCompra::class, 'orden');
    }

    public function index(Request $request): View
    {
        $estados = ['borrador', 'enviada', 'recibida_parcial', 'recibida_total', 'cancelada'];

        $ordenes = OrdenCompra::query()
            ->with('proveedor')
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->string('estado')->toString()))
            ->when($request->filled('proveedor_id'), fn ($query) => $query->where('proveedor_id', $request->integer('proveedor_id')))
            ->when($request->filled('desde'), fn ($query) => $query->whereDate('fecha_emision', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn ($query) => $query->whereDate('fecha_emision', '<=', $request->date('hasta')))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.compras.index', [
            'ordenes' => $ordenes,
            'estados' => $estados,
            'proveedores' => Proveedor::query()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function create(): View
    {
        return view('admin.compras.create', [
            'proveedores' => Proveedor::query()->activos()->whereIn('tipo', ['Mayorista', 'Importador'])->orderBy('nombre')->get(),
            'productos' => Producto::query()->orderBy('nombre')->get(['id', 'nombre', 'precio_unitario']),
        ]);
    }

    public function store(StoreOrdenCompraRequest $request): RedirectResponse
    {
        $orden = $this->ordenes->crear(
            Proveedor::findOrFail((int) $request->validated('proveedor_id')),
            $request->user(),
            $request->validated('items'),
            [
                'fecha_emision' => $request->validated('fecha_emision'),
                'fecha_entrega' => $request->validated('fecha_entrega'),
                'impuestos' => $request->validated('impuestos') ?? 0,
                'notas' => $request->validated('notas'),
            ]
        );

        return redirect()
            ->route('admin.compras.show', $orden)
            ->with('success', "Orden de compra {$orden->numero} creada exitosamente.");
    }

    public function show(OrdenCompra $orden): View
    {
        $orden->load(['proveedor', 'usuario', 'detalles.producto', 'recepciones.user']);

        $lineas = $orden->detalles->map(function (OrdenCompraDetalle $detalle): array {
            $recibido = $this->ordenes->recibidoDe($detalle);

            return [
                'detalle' => $detalle,
                'recibido' => $recibido,
                'pendiente' => max((int) $detalle->cantidad - $recibido, 0),
            ];
        });

        $puedeRecibir = in_array($orden->estado, ['enviada', 'recibida_parcial'], true);

        return view('admin.compras.show', compact('orden', 'lineas', 'puedeRecibir'));
    }

    /**
     * Registra una recepción parcial o total desde el detalle de la orden.
     */
    public function recibir(Request $request, OrdenCompra $orden): RedirectResponse
    {
        abort_unless($request->user()->can('recibir ordenes-compra'), 403);

        // El modal envía una cantidad por línea, incluidas las que quedan en 0.
        // Se descartan antes de validar para no rechazar una recepción válida.
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn ($item) => (int) ($item['cantidad'] ?? 0) > 0)
                ->values()
                ->all(),
        ]);

        $datos = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.orden_compra_item_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ], [
            'items.required' => 'Ingresá al menos una cantidad recibida.',
            'items.min' => 'Ingresá al menos una cantidad recibida.',
            'items.*.cantidad.min' => 'La cantidad a recibir debe ser mayor que cero.',
        ]);

        try {
            $this->ordenes->recibir(
                $orden,
                $request->user(),
                $datos['items'],
                ['notas' => $datos['notas'] ?? null]
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.compras.show', $orden)
            ->with('success', "Recepción registrada para la orden {$orden->numero}.");
    }

    /**
     * Vista previa del documento antes de emitir la orden.
     */
    public function preview(OrdenCompra $orden): Response
    {
        $this->authorize('view', $orden);
        $orden->load(['proveedor', 'usuario', 'detalles.producto']);

        return Pdf::loadView('admin.compras.pdf', ['orden' => $orden])
            ->setPaper('a4', 'portrait')
            ->stream("vista-previa-orden-{$orden->numero}.pdf");
    }

    /**
     * Emite formalmente una orden borrador, cambiando su estado a enviada.
     * No modifica el esquema: reutiliza el estado existente.
     */
    public function emitir(OrdenCompra $orden): RedirectResponse
    {
        $this->authorize('update', $orden);
        $orden->load('proveedor', 'detalles');

        if ($orden->estado !== 'borrador') {
            return back()->with('error', 'Solo se pueden emitir órdenes que estén en borrador.');
        }

        if (! $orden->proveedor || ! $orden->proveedor->activo) {
            return back()->with('error', 'El proveedor debe estar registrado y activo para emitir la orden.');
        }

        if ($orden->detalles->isEmpty() || (float) $orden->total <= 0) {
            return back()->with('error', 'La orden debe tener productos y un total válido antes de emitirla.');
        }

        $orden->update(['estado' => 'enviada']);

        return redirect()->route('admin.compras.show', $orden)
            ->with('success', "La orden {$orden->numero} fue emitida correctamente. Ya puedes descargar el PDF y enviarlo al proveedor.");
    }

    /**
     * Genera el PDF A4 de una orden de compra.
     *
     * El permiso es el `view` de OrdenCompraPolicy: quien puede ver la orden en
     * el panel puede imprimirla; no se agrega un permiso nuevo.
     */
    public function pdf(OrdenCompra $orden): Response
    {
        $this->authorize('view', $orden);

        $orden->load(['proveedor', 'usuario', 'detalles.producto']);

        $pdf = Pdf::loadView('admin.compras.pdf', ['orden' => $orden])
            ->setPaper('a4', 'portrait');

        return $pdf->download("orden-compra-{$orden->numero}.pdf");
    }
}
