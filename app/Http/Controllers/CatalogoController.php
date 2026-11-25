<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AtributoTecnico;
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
 * precio_min, precio_max, orden, atributos[id]=valor, page.
 *
 * `atributos` filtra por atributos EAV (`producto_atributos`) usando el id del
 * atributo como clave: un valor de texto se compara con `valor_string` o con
 * el valor del enum asociado, nunca con SQL armado a mano.
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
            'atributos' => ['nullable', 'array'],
            'atributos.*' => ['nullable', 'string', 'max:100'],
        ]);

        $orden = $filtros['orden'] ?? 'novedades';
        [$columna, $direccion] = self::ORDENES[$orden];

        $atributosAplicados = $this->atributosAplicados($filtros['atributos'] ?? []);

        $productos = Producto::query()
            ->visible()
            ->with(['marca', 'categoria', 'imagenesGaleria'])
            ->when(filled($filtros['buscar'] ?? null), fn ($query) => $query->buscar($filtros['buscar']))
            ->when(filled($filtros['categoria_id'] ?? null), fn ($query) => $query->where('categoria_id', $filtros['categoria_id']))
            ->when(filled($filtros['marca_id'] ?? null), fn ($query) => $query->where('marca_id', $filtros['marca_id']))
            ->when(filled($filtros['precio_min'] ?? null), fn ($query) => $query->where('precio_unitario', '>=', $filtros['precio_min']))
            ->when(filled($filtros['precio_max'] ?? null), fn ($query) => $query->where('precio_unitario', '<=', $filtros['precio_max']))
            ->when($atributosAplicados !== [], fn ($query) => $this->filtrarAtributos($query, $atributosAplicados))
            ->orderBy($columna, $direccion)
            ->orderBy('id', $direccion)
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $categorias = Categoria::query()->orderBy('nombre')->get(['id', 'nombre']);
        $marcas = Marca::query()->orderBy('nombre')->get(['id', 'nombre']);
        $atributosFiltro = $this->atributosFiltrables($filtros['categoria_id'] ?? null);

        $favoritosIds = $this->favoritos->ids($request->user(), $request->cookie('favoritos_token'));

        return view('catalogo.index', compact(
            'productos', 'categorias', 'marcas', 'filtros',
            'favoritosIds', 'atributosFiltro', 'atributosAplicados'
        ));
    }

    /**
     * Normaliza `atributos[id]=valor` a un mapa de atributos filtrables reales.
     * Un id inexistente o no filtrable se descarta sin romper el listado.
     *
     * @param  array<array-key, mixed>  $entrada
     * @return array<int, string>
     */
    private function atributosAplicados(array $entrada): array
    {
        $limpios = [];

        foreach ($entrada as $atributoId => $valor) {
            $valor = is_scalar($valor) ? trim((string) $valor) : '';

            if (is_numeric($atributoId) && $valor !== '') {
                $limpios[(int) $atributoId] = $valor;
            }
        }

        if ($limpios === []) {
            return [];
        }

        $validos = AtributoTecnico::query()
            ->whereIn('id', array_keys($limpios))
            ->where('es_filtrable', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_intersect_key($limpios, array_flip($validos));
    }

    /**
     * Cada atributo se resuelve en una subconsulta: el producto debe tener ese
     * atributo con ese valor (texto, número o valor de enum).
     *
     * @param  array<int, string>  $atributosAplicados
     */
    private function filtrarAtributos($query, array $atributosAplicados)
    {
        foreach ($atributosAplicados as $atributoId => $valor) {
            $query->whereHas('atributos', function ($subconsulta) use ($atributoId, $valor) {
                $subconsulta->where('atributo_id', $atributoId)
                    ->where(function ($valorConsulta) use ($valor) {
                        $valorConsulta->where('valor_string', 'ILIKE', '%'.$valor.'%')
                            ->orWhereHas('valorEnum', fn ($enum) => $enum->where('valor', 'ILIKE', $valor));

                        if (is_numeric($valor)) {
                            $valorConsulta->orWhere('valor_integer', (int) $valor)
                                ->orWhere('valor_decimal', (float) $valor);
                        }
                    });
            });
        }

        return $query;
    }

    /**
     * Atributos que se ofrecen como filtro en la barra lateral. Si hay categoría
     * seleccionada se muestran los suyos y los globales; si no, los globales y,
     * cuando no existe ninguno, los primeros filtrables para no dejar la barra
     * vacía.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AtributoTecnico>
     */
    private function atributosFiltrables(mixed $categoriaId)
    {
        $base = fn () => AtributoTecnico::query()
            ->where('es_filtrable', true)
            ->with(['valoresPredefinidos' => fn ($query) => $query->orderBy('orden')->orderBy('valor')])
            ->orderBy('orden')
            ->orderBy('nombre');

        // El mismo atributo existe una vez por categoría ("socket" en placas y
        // en memorias). Sin categoría seleccionada se muestra una sola vez para
        // no repetir el mismo filtro seis veces en la barra lateral.
        $deduplicar = fn ($atributos) => $atributos
            ->unique(fn (AtributoTecnico $atributo) => mb_strtolower($atributo->nombre))
            ->values()
            ->take(6);

        if (filled($categoriaId)) {
            return $deduplicar($base()
                ->where(fn ($query) => $query->whereNull('categoria_id')->orWhere('categoria_id', $categoriaId))
                ->get());
        }

        $globales = $base()->whereNull('categoria_id')->get();

        return $deduplicar($globales->isNotEmpty() ? $globales : $base()->get());
    }

    public function show(Request $request, string $slug): View
    {
        // `productos` no tiene columna `activo`: la visibilidad pública se
        // controla con `visible_catalogo` (schema canónico).
        $producto = Producto::query()
            ->with(['marca', 'categoria', 'imagenesGaleria', 'atributos.atributo', 'atributos.valorEnum'])
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
