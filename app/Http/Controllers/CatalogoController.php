<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\FavoritoService;
use App\Services\ProductoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catálogo público.
 *
 * Día 1: ficha básica. El layout público definitivo y los filtros llegan
 * en el Día 2+.
 */
class CatalogoController extends Controller
{
    public function __construct(
        private readonly ProductoService $productos,
        private readonly FavoritoService $favoritos
    ) {
        //
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
