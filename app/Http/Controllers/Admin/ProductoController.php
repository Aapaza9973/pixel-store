<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductoRequest;
use App\Http\Requests\Admin\UpdateProductoRequest;
use App\Models\AtributoTecnico;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoAtributo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Producto::class, 'producto');
    }

    public function index(): View
    {
        $productos = Producto::query()
            ->with(['categoria', 'marca'])
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.productos.index', compact('productos'));
    }

    public function create(): View
    {
        $categorias = Categoria::orderBy('nombre')->get();
        $marcas = Marca::orderBy('nombre')->get();
        $atributos = AtributoTecnico::orderBy('orden')->get();

        return view('admin.productos.create', compact('categorias', 'marcas', 'atributos'));
    }

    public function store(StoreProductoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $producto = Producto::create($request->safe()->except('atributos'));
            $this->guardarAtributos($producto, $request->input('atributos', []));
        });

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    public function show(Producto $producto): View
    {
        $producto->load(['categoria', 'marca', 'atributos.atributo', 'atributos.valorEnum']);

        return view('admin.productos.show', compact('producto'));
    }

    public function edit(Producto $producto): View
    {
        $categorias = Categoria::orderBy('nombre')->get();
        $marcas = Marca::orderBy('nombre')->get();
        $atributos = AtributoTecnico::orderBy('orden')->get();

        $valoresActuales = $producto->atributos()
            ->pluck('valor_string', 'atributo_id')
            ->toArray();

        return view('admin.productos.edit', compact('producto', 'categorias', 'marcas', 'atributos', 'valoresActuales'));
    }

    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        DB::transaction(function () use ($request, $producto) {
            $producto->update($request->safe()->except('atributos'));

            $producto->atributos()->delete();
            $this->guardarAtributos($producto, $request->input('atributos', []));
        });

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        if ($producto->detalleVentas()->exists()) {
            return redirect()
                ->route('admin.productos.index')
                ->with('error', 'No se puede eliminar: el producto tiene ventas asociadas.');
        }

        $producto->delete();

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }

    private function guardarAtributos(Producto $producto, array $atributos): void
    {
        foreach ($atributos as $atributoId => $valor) {
            if ($valor === null || $valor === '') {
                continue;
            }

            $atributo = AtributoTecnico::find($atributoId);
            if (!$atributo) {
                continue;
            }

            $datos = ['producto_id' => $producto->id, 'atributo_id' => $atributoId];

            match ($atributo->tipo_dato) {
                'string' => $datos['valor_string'] = $valor,
                'integer' => $datos['valor_integer'] = (int) $valor,
                'decimal' => $datos['valor_decimal'] = (float) $valor,
                'boolean' => $datos['valor_boolean'] = (bool) $valor,
                'enum' => $datos['valor_enum_id'] = $valor,
                default => null,
            };

            ProductoAtributo::create($datos);
        }
    }
}
