<?php

namespace App\Http\Controllers\Shop;

use App\Contracts\CartInterface;
use App\Exceptions\ShopException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutRequest;
use App\Services\CheckoutService;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartInterface $cart,
        private readonly CheckoutService $checkout,
    ) {}

    public function create()
    {
        if ($this->cart->count() === 0) {
            return redirect()->route('shop.index')->with('error', 'Tu carrito está vacío.');
        }

        return view('checkout.create', ['lines' => $this->cart->lines(), 'total' => $this->cart->total()]);
    }

    public function store(CheckoutRequest $request)
    {
        try {
            $token = $this->checkout->crearPedido($request->validated());
        } catch (ShopException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        return redirect()->route('checkout.success', $token);
    }

    public function success(string $token)
    {
        $pedido = $this->checkout->obtenerPorToken($token);
        abort_if($pedido === null, 404);

        return view('checkout.success', ['pedido' => $pedido]);
    }
}
