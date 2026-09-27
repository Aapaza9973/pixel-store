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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        return view('admin.productos.create', [
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'atributosPorCategoria' => $this->atributosPorCategoria(),
        ]);
    }

    public function store(StoreProductoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $producto = Producto::create($request->safe()->except(['atributos', 'imagen']));

            $this->guardarImagen($request, $producto);
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
        $producto->load(['atributos.atributo', 'atributos.valorEnum']);

        return view('admin.productos.edit', [
            'producto' => $producto,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'atributosPorCategoria' => $this->atributosPorCategoria(),
            'valoresActuales' => $this->valoresActuales($producto),
        ]);
    }

    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        DB::transaction(function () use ($request, $producto) {
            $producto->update($request->safe()->except(['atributos', 'imagen']));

            $this->guardarImagen($request, $producto);

            $producto->atributos()->delete();
            $this->guardarAtributos($producto, $request->input('atributos', []));
        });

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        if (DB::table('detalle_ventas')->where('producto_id', $producto->id)->exists()) {
            return redirect()
                ->route('admin.productos.index')
                ->with('error', 'No se puede eliminar: el producto tiene ventas asociadas.');
        }

        $producto->delete();

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }

    /**
     * Atributos EAV agrupados por categoría para el formulario dinámico.
     */
    private function atributosPorCategoria(): array
    {
        return AtributoTecnico::query()
            ->with(['valoresPredefinidos' => fn ($query) => $query->orderBy('orden')])
            ->orderBy('orden')
            ->get()
            ->groupBy('categoria_id')
            ->map(fn ($grupo) => $grupo->map(fn (AtributoTecnico $atributo) => [
                'id' => $atributo->id,
                'nombre' => $atributo->nombre,
                'tipo_dato' => $atributo->tipo_dato,
                'unidad' => $atributo->unidad,
                'valores' => $atributo->valoresPredefinidos
                    ->map(fn ($valor) => ['id' => $valor->id, 'valor' => $valor->valor])
                    ->values()
                    ->all(),
            ])->values()->all())
            ->all();
    }

    /**
     * Valores EAV actuales del producto, indexados por atributo_id.
     * El enum guarda el id y el boolean se normaliza a 1/0.
     */
    private function valoresActuales(Producto $producto): array
    {
        return $producto->atributos->mapWithKeys(function (ProductoAtributo $item) {
            $valor = match ($item->atributo?->tipo_dato) {
                'enum' => $item->valor_enum_id,
                'integer' => $item->valor_integer,
                'decimal' => $item->valor_decimal,
                'boolean' => $item->valor_boolean ? 1 : 0,
                default => $item->valor_string,
            };

            return [$item->atributo_id => $valor];
        })->all();
    }

    /**
     * Guarda la imagen principal en storage/app/public/productos.
     */
    private function guardarImagen(Request $request, Producto $producto): void
    {
        if (! $request->hasFile('imagen')) {
            return;
        }

        if ($producto->imagen_principal) {
            Storage::disk('public')->delete($producto->imagen_principal);
        }

        $ruta = $request->file('imagen')->store('productos', 'public');

        $producto->update(['imagen_principal' => $ruta]);
    }

    /**
     * Persiste los valores EAV enviados como atributos[atributo_id].
     */
    private function guardarAtributos(Producto $producto, array $atributos): void
    {
        foreach ($atributos as $atributoId => $valor) {
            if ($valor === null || $valor === '') {
                continue;
            }

            $atributo = AtributoTecnico::find($atributoId);

            if (! $atributo) {
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
