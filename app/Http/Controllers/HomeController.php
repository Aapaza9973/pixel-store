<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Services\FavoritoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Home pública de la tienda.
 *
 * Es la puerta de entrada del catálogo: no requiere sesión y no muestra nada
 * del panel. Los destacados salen de la misma tabla `productos` que usa el
 * inventario, así que el stock que se ve es el stock real.
 */
class HomeController extends Controller
{
    public function __construct(
        private readonly FavoritoService $favoritos
    ) {}

    public function index(Request $request): View
    {
        $destacados = Producto::query()
            ->visible()
            ->with(['marca', 'categoria', 'imagenesGaleria'])
            ->where('stock', '>', 0)
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $categorias = Categoria::query()
            ->whereHas('productos', fn ($query) => $query->visible())
            ->withCount(['productos' => fn ($query) => $query->visible()])
            ->orderBy('nombre')
            ->limit(8)
            ->get();

        $totalProductos = Producto::query()->visible()->count();

        $favoritosIds = $this->favoritos->ids($request->user(), $request->cookie('favoritos_token'));

        return view('home', compact('destacados', 'categorias', 'totalProductos', 'favoritosIds'));
    }
}
