<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Services\FavoritoService;
use App\Services\ProductoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Catálogo público.
 *
 * Día 1: ficha básica. Día 2: listado con búsqueda, filtros de categoría,
 * marca y rango de precio, más orden configurable (HU-2.3, 2.6).
 * El layout público definitivo llega cuando exista `layouts.public`.
 *
 * Query params (GET /catalogo): buscar, categoria_id, marca_id,
 * precio_min, precio_max, orden, page.
 */
class CatalogoController extends Controller
{
    /** Cards por página del listado público. */
    private const POR_PAGINA = 12;

    /**
     * Órdenes permitidos: clave pública => [columna, dirección].
     */
    private const ORDENES = [
        'novedades' => ['created_at', 'desc'],
        'precio_asc' => ['precio_unitario', 'asc'],
        'precio_desc' => ['precio_unitario', 'desc'],
        'nombre_asc' => ['nombre', 'asc'],
        'nombre_desc' => ['nombre', 'desc'],
    ];

    public function __construct(
        private readonly ProductoService $productos,
        private readonly FavoritoService $favoritos
    ) {
        //
    }

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'categoria_id' => ['nullable', 'integer', Rule::exists('categorias', 'id')],
            'marca_id' => ['nullable', 'integer', Rule::exists('marcas', 'id')],
            'precio_min' => ['nullable', 'numeric', 'min:0'],
            'precio_max' => ['nullable', 'numeric', 'min:0'],
            'orden' => ['nullable', 'string', Rule::in(array_keys(self::ORDENES))],
        ]);

        $orden = $filtros['orden'] ?? 'novedades';
        [$columna, $direccion] = self::ORDENES[$orden];

        $productos = Producto::query()
            ->visible()
            ->with(['marca', 'categoria', 'imagenesGaleria'])
            ->when(filled($filtros['buscar'] ?? null), fn ($query) => $query->buscar($filtros['buscar']))
            ->when(filled($filtros['categoria_id'] ?? null), fn ($query) => $query->where('categoria_id', $filtros['categoria_id']))
            ->when(filled($filtros['marca_id'] ?? null), fn ($query) => $query->where('marca_id', $filtros['marca_id']))
            ->when(filled($filtros['precio_min'] ?? null), fn ($query) => $query->where('precio_unitario', '>=', $filtros['precio_min']))
            ->when(filled($filtros['precio_max'] ?? null), fn ($query) => $query->where('precio_unitario', '<=', $filtros['precio_max']))
            ->orderBy($columna, $direccion)
            ->orderBy('id', $direccion)
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $categorias = Categoria::query()->orderBy('nombre')->get(['id', 'nombre']);
        $marcas = Marca::query()->orderBy('nombre')->get(['id', 'nombre']);

        $favoritosIds = $this->favoritos->ids($request->user(), $request->cookie('favoritos_token'));

        return view('catalogo.index', compact('productos', 'categorias', 'marcas', 'filtros', 'favoritosIds'));
    }

    public function show(Request $request, string $slug): View
    {
        // `productos` no tiene columna `activo`: la visibilidad pública se
        // controla con `visible_catalogo` (schema canónico).
        $producto = Producto::query()
            ->with(['marca', 'categoria', 'imagenesGaleria'])
            ->where('slug', $slug)
            ->where('visible_catalogo', true)
            ->firstOrFail();

        // 2.8.4: el producto puede ocultarse del catálogo mientras no tenga stock.
        if ($producto->ocultar_si_sin_stock && (int) $producto->stock <= 0) {
            abort(404);
        }

        $similares = $this->productos->similares($producto);
        $alternativas = $this->productos->alternativas($producto);

        $favoritosIds = $this->favoritos->ids($request->user(), $request->cookie('favoritos_token'));

        return view('catalogo.producto', compact('producto', 'similares', 'alternativas', 'favoritosIds'));
    }
}
