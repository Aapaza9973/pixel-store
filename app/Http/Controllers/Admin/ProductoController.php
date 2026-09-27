<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductoRequest;
use App\Http\Requests\Admin\TransferirStockRequest;
use App\Http\Requests\Admin\UpdateProductoRequest;
use App\Models\AtributoTecnico;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoAtributo;
use App\Models\Ubicacion;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function __construct(private readonly InventoryService $inventory)
    {
        $this->authorizeResource(Producto::class, 'producto');
    }

    public function index(Request $request): View
    {
        $query = Producto::query()->with(['categoria', 'marca']);

        if ($request->filled('buscar')) {
            $query->buscar($request->string('buscar')->trim()->toString());
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->integer('categoria_id'));
        }

        if ($request->filled('marca_id')) {
            $query->where('marca_id', $request->integer('marca_id'));
        }

        if ($request->filled('precio_min') && $request->filled('precio_max')) {
            $query->whereBetween('precio_unitario', [
                $request->input('precio_min'),
                $request->input('precio_max'),
            ]);
        } elseif ($request->filled('precio_min')) {
            $query->where('precio_unitario', '>=', $request->input('precio_min'));
        } elseif ($request->filled('precio_max')) {
            $query->where('precio_unitario', '<=', $request->input('precio_max'));
        }

        switch ($request->input('disponibilidad')) {
            case 'con_stock':
                $query->where('stock', '>', 0);
                break;
            case 'agotado':
                $query->where('stock', 0);
                break;
            case 'bajo_stock':
                $query->whereColumn('stock', '<=', 'umbral_alerta');
                break;
        }

        $this->aplicarFiltrosAtributos($request, $query);

        match ($request->input('orden')) {
            'precio_asc' => $query->orderBy('precio_unitario'),
            'precio_desc' => $query->orderByDesc('precio_unitario'),
            'nombre_asc' => $query->orderBy('nombre'),
            'nombre_desc' => $query->orderByDesc('nombre'),
            'recientes' => $query->orderByDesc('id'),
            'stock_bajo' => $query->orderBy('stock'),
            default => $query->orderByDesc('id'),
        };

        $productos = $query->paginate(15)->withQueryString();

        return view('admin.productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'atributosFiltrables' => $this->atributosFiltrables(),
        ]);
    }

    public function create(): View
    {
        return view('admin.productos.create', [
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Marca::orderBy('nombre')->get(),
            'atributosPorCategoria' => $this->atributosPorCategoria(),
            'ubicaciones' => $this->ubicacionesActivas(),
            'ubicacionPorDefecto' => $this->ubicacionPorDefecto(),
        ]);
    }

    public function store(StoreProductoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            // El stock inicial nunca se escribe directo: pasa por InventoryService.
            $datos = $request->safe()->except(['atributos', 'imagen', 'stock', 'ubicacion_id']);
            $datos['stock'] = 0;

            $producto = Producto::create($datos);

            $this->guardarImagen($request, $producto);
            $this->guardarAtributos($producto, $request->input('atributos', []));

            $stockInicial = (int) $request->input('stock', 0);

            if ($stockInicial > 0) {
                $this->inventory->ajustarStock(
                    $producto,
                    $stockInicial,
                    'entrada',
                    'Stock inicial',
                    $this->resolverUbicacionId($request->input('ubicacion_id')),
                    $request->user()->id
                );
            }
        });

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    public function show(Producto $producto): View
    {
        $producto->load([
            'categoria', 'marca', 'atributos.atributo', 'atributos.valorEnum',
            'stockUbicaciones.ubicacion',
        ]);

        return view('admin.productos.show', [
            'producto' => $producto,
            'ubicaciones' => $this->ubicacionesActivas(),
        ]);
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
            'ubicaciones' => $this->ubicacionesActivas(),
            'ubicacionPorDefecto' => $this->ubicacionPorDefecto(),
            'ubicacionActual' => $producto->stockUbicaciones()->orderByDesc('cantidad')->value('ubicacion_id'),
        ]);
    }

    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        DB::transaction(function () use ($request, $producto) {
            $stockActual = (int) $producto->stock;

            // El stock no se actualiza directo: se calcula el delta y pasa por InventoryService.
            $producto->update($request->safe()->except(['atributos', 'imagen', 'stock', 'ubicacion_id']));

            $this->guardarImagen($request, $producto);

            $producto->atributos()->delete();
            $this->guardarAtributos($producto, $request->input('atributos', []));

            $stockNuevo = (int) $request->input('stock', $stockActual);
            $delta = $stockNuevo - $stockActual;

            if ($delta !== 0) {
                $this->inventory->ajustarStock(
                    $producto,
                    $delta,
                    'ajuste',
                    'Ajuste manual desde edición',
                    $this->resolverUbicacionId($request->input('ubicacion_id')),
                    $request->user()->id
                );
            }
        });

        return redirect()
            ->route('admin.productos.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Transfiere stock entre dos ubicaciones usando InventoryService.
     */
    public function transferirStock(TransferirStockRequest $request, Producto $producto): RedirectResponse
    {
        try {
            $this->inventory->transferir(
                $producto,
                $request->integer('ubicacion_origen_id'),
                $request->integer('ubicacion_destino_id'),
                $request->integer('cantidad'),
                $request->user()->id
            );
        } catch (StockInsuficienteException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.productos.show', $producto)
            ->with('success', 'Stock transferido exitosamente.');
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
     * Ubicaciones activas ordenadas (tienda primero) para selectores de stock.
     */
    private function ubicacionesActivas()
    {
        return Ubicacion::query()
            ->where('activa', true)
            ->orderByRaw("CASE WHEN tipo = 'tienda' THEN 0 ELSE 1 END")
            ->orderBy('pasillo')
            ->orderBy('estante')
            ->orderBy('anaquel')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Resuelve la ubicación destino del movimiento: la enviada o la tienda por defecto.
     */
    private function resolverUbicacionId(mixed $ubicacionId): ?int
    {
        if ($ubicacionId !== null && $ubicacionId !== '') {
            return (int) $ubicacionId;
        }

        return $this->ubicacionPorDefecto();
    }

    /**
     * Primera ubicación de tipo tienda (destino por defecto del stock).
     */
    private function ubicacionPorDefecto(): ?int
    {
        return Ubicacion::query()
            ->where('tipo', 'tienda')
            ->orderBy('id')
            ->value('id');
    }

    /**
     * Aplica los filtros EAV recibidos como atributos[atributo_id] = valor.
     * Se compara solo la columna correspondiente al tipo_dato del atributo
     * para evitar castings inválidos en PostgreSQL (p. ej. 'AM4' contra integer).
     */
    private function aplicarFiltrosAtributos(Request $request, $query): void
    {
        $atributos = (array) $request->input('atributos', []);

        foreach ($atributos as $atributoId => $valor) {
            if ($valor === null || $valor === '') {
                continue;
            }

            $atributo = AtributoTecnico::find($atributoId);

            if (! $atributo) {
                continue;
            }

            $query->whereHas('atributos', function ($q) use ($atributo, $valor) {
                $q->where('atributo_id', $atributo->id)
                    ->where(function ($sub) use ($atributo, $valor) {
                        match ($atributo->tipo_dato) {
                            'enum' => $sub->where('valor_enum_id', $valor)
                                ->orWhereHas('valorEnum', fn ($v) => $v->where('valor', $valor)),
                            'integer' => $sub->where('valor_integer', (int) $valor),
                            'decimal' => $sub->where('valor_decimal', (float) $valor),
                            'boolean' => $sub->where('valor_boolean', filter_var($valor, FILTER_VALIDATE_BOOLEAN)),
                            default => $sub->where('valor_string', 'ILIKE', "%{$valor}%"),
                        };
                    });
            });
        }
    }

    /**
     * Atributos marcados como filtrables, agrupados por categoría.
     */
    private function atributosFiltrables(): array
    {
        return AtributoTecnico::query()
            ->where('es_filtrable', true)
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
