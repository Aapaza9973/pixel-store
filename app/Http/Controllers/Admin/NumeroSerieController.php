<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNumeroSerieRequest;
use App\Http\Requests\Admin\UpdateNumeroSerieRequest;
use App\Models\NumeroSerie;
use App\Models\Producto;
use App\Models\Ubicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trazabilidad de hardware (HU-2.2): alta, consulta y garantía por serie.
 */
class NumeroSerieController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(NumeroSerie::class, 'numeroSerie');
    }

    public function index(Request $request): View
    {
        $buscar = $request->string('buscar')->trim()->toString();
        $estado = $request->string('estado')->toString();

        $series = NumeroSerie::query()
            ->with(['producto:id,nombre,sku', 'cliente:id,nombre'])
            ->when($buscar !== '', fn ($query) => $query->buscar($buscar))
            ->when($estado !== '', fn ($query) => $query->where('estado', $estado))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.series.index', [
            'series' => $series,
            'estados' => NumeroSerie::ESTADOS,
            'buscar' => $buscar,
            'estado' => $estado,
        ]);
    }

    public function create(): View
    {
        return view('admin.series.create', [
            'productos' => $this->productos(),
            'ubicaciones' => $this->ubicaciones(),
        ]);
    }

    public function store(StoreNumeroSerieRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['estado'] = 'disponible';
        $datos['fecha_ingreso'] = $datos['fecha_ingreso'] ?? now();

        $serie = NumeroSerie::create($datos);

        return redirect()
            ->route('admin.series.show', $serie)
            ->with('success', 'Número de serie registrado.');
    }

    public function show(NumeroSerie $numeroSerie): View
    {
        $numeroSerie->load(['producto', 'venta.vendedor:id,name', 'cliente', 'ubicacion']);

        return view('admin.series.show', ['serie' => $numeroSerie]);
    }

    public function edit(NumeroSerie $numeroSerie): View
    {
        return view('admin.series.edit', [
            'serie' => $numeroSerie,
            'estados' => NumeroSerie::ESTADOS,
            'ubicaciones' => $this->ubicaciones(),
        ]);
    }

    public function update(UpdateNumeroSerieRequest $request, NumeroSerie $numeroSerie): RedirectResponse
    {
        $numeroSerie->update($request->validated());

        return redirect()
            ->route('admin.series.show', $numeroSerie)
            ->with('success', 'Número de serie actualizado.');
    }

    public function destroy(NumeroSerie $numeroSerie): RedirectResponse
    {
        if (! $numeroSerie->estaDisponible()) {
            return redirect()
                ->route('admin.series.index')
                ->with('error', 'Solo se puede eliminar una serie disponible; las vendidas son trazabilidad.');
        }

        $numeroSerie->delete();

        return redirect()
            ->route('admin.series.index')
            ->with('success', 'Número de serie eliminado.');
    }

    /**
     * Productos que manejan serie; si ninguno está marcado se listan todos para
     * no dejar el formulario inutilizable.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Producto>
     */
    private function productos()
    {
        $productos = Producto::query()
            ->where('maneja_numero_serie', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'sku']);

        return $productos->isEmpty()
            ? Producto::query()->orderBy('nombre')->get(['id', 'nombre', 'sku'])
            : $productos;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Ubicacion>
     */
    private function ubicaciones()
    {
        return Ubicacion::query()->orderBy('nombre')->get(['id', 'nombre']);
    }
}
