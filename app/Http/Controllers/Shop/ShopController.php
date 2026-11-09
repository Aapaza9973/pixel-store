<?php

namespace App\Http\Controllers\Shop;

use App\Contracts\ProductoRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\ProductoFilterRequest;

class ShopController extends Controller
{
    public function __construct(private readonly ProductoRepositoryInterface $productos) {}

    public function home()
    {
        return view('shop.home', ['destacados' => $this->productos->destacados(8)]);
    }

    public function index(ProductoFilterRequest $request)
    {
        $filtros = $request->filtros();

        return view('shop.index', [
            'productos' => $this->productos->paginarFiltrados($filtros, 12),
            'filtros' => $filtros,
            'categorias' => $this->productos->categorias(),
            'marcas' => $this->productos->marcas(),
        ]);
    }

    public function show(string $ref)
    {
        $producto = $this->productos->buscarPorReferencia($ref);
        abort_if($producto === null, 404);

        return view('shop.show', [
            'producto' => $producto,
            'galeria' => $this->productos->galeria($producto->id),
            'specs' => $this->productos->especificaciones($producto->id),
            'relacionados' => $this->productos->relacionados($producto->id, $producto->categoria_id),
        ]);
    }
}
