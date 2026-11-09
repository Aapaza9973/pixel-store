<?php

namespace App\Http\Controllers\Shop;

use App\Contracts\CartInterface;
use App\Contracts\ProductoRepositoryInterface;
use App\Exceptions\ShopException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartInterface $cart,
        private readonly ProductoRepositoryInterface $productos,
    ) {}

    public function index()
    {
        return view('cart.index', ['lines' => $this->cart->lines(), 'total' => $this->cart->total()]);
    }

    public function store(Request $request, int $producto)
    {
        $data = $request->validate(['cantidad' => ['nullable', 'integer', 'min:1', 'max:99']]);
        $item = $this->productos->buscarPorIds([$producto])->first();
        abort_if($item === null, 404);

        try {
            $this->cart->add($producto, $data['cantidad'] ?? 1);
        } catch (ShopException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', "«{$item->nombre}» se agregó al carrito.");
    }

    public function update(Request $request, int $producto)
    {
        $data = $request->validate(['cantidad' => ['required', 'integer', 'min:0', 'max:99']]);

        try {
            $this->cart->update($producto, $data['cantidad']);
        } catch (ShopException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    public function destroy(int $producto)
    {
        $this->cart->remove($producto);

        return back()->with('ok', 'Producto quitado del carrito.');
    }
}
