<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCotizacionRequest;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Services\CotizacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CotizacionController extends Controller
{
    public function __construct(private readonly CotizacionService $cotizaciones)
    {
        $this->authorizeResource(Cotizacion::class, 'cotizacion');
    }

    public function index(Request $request): View
    {
        $query = Cotizacion::query()->with(['items', 'vendedor']);

        if ($request->filled('buscar')) {
            $query->buscar($request->string('buscar')->trim()->toString());
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado')->trim()->toString());
        }

        $cotizaciones = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.cotizaciones.index', [
            'cotizaciones' => $cotizaciones,
            'estados' => Cotizacion::ESTADOS,
        ]);
    }

    public function create(): View
    {
        return view('admin.cotizaciones.create', [
            'clientes' => Cliente::orderBy('nombre')->get(),
            'productos' => Producto::query()->where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(StoreCotizacionRequest $request): RedirectResponse
    {
        $cotizacion = $this->cotizaciones->crear(
            $request->validated(),
            $request->validated('items'),
            $request->user()
        );

        return redirect()
            ->route('admin.cotizaciones.show', $cotizacion)
            ->with('success', "Cotización {$cotizacion->numero} creada exitosamente.");
    }

    public function show(Cotizacion $cotizacion): View
    {
        $cotizacion->load(['items.producto', 'vendedor', 'cliente']);

        return view('admin.cotizaciones.show', compact('cotizacion'));
    }

    public function pdf(Cotizacion $cotizacion): Response
    {
        $this->authorize('view', $cotizacion);

        $pdf = $this->cotizaciones->generarPdf($cotizacion);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cotizacion-'.$cotizacion->numero.'.pdf"',
        ]);
    }

    public function convertir(Cotizacion $cotizacion): RedirectResponse
    {
        $this->authorize('convertir', $cotizacion);

        try {
            $venta = $this->cotizaciones->convertirAVenta($cotizacion, request()->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.ventas.show', $venta)
            ->with('success', "Cotización {$cotizacion->numero} convertida en venta #{$venta->id}.");
    }

    public function anular(Cotizacion $cotizacion): RedirectResponse
    {
        $this->authorize('anular', $cotizacion);

        $this->cotizaciones->anular($cotizacion);

        return redirect()
            ->route('admin.cotizaciones.show', $cotizacion)
            ->with('success', "Cotización {$cotizacion->numero} anulada.");
    }
}
