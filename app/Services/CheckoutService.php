<?php

namespace App\Services;

use App\Contracts\CartInterface;
use App\DTOs\CartLine;
use App\Exceptions\ShopException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Registra el pedido del cliente (tablas pedidos + pedido_items) en estado "Pendiente".
 *
 * NO descuenta stock: el pedido pasa a venta cuando el personal lo confirma
 * (pedidos.venta_id) y ahí InventoryService, único punto de mutación de stock,
 * registra la salida. Aquí solo se valida que haya stock al momento de pedir.
 */
final class CheckoutService
{
    public function __construct(private readonly CartInterface $cart) {}

    /** @return string token público del pedido (48 caracteres) */
    public function crearPedido(array $cliente): string
    {
        $lines = $this->cart->lines();

        if ($lines->isEmpty()) {
            throw ShopException::carritoVacio();
        }

        $token = DB::transaction(function () use ($cliente, $lines) {
            $stocks = DB::table('productos')
                ->whereIn('id', $lines->map(fn (CartLine $l) => $l->producto->id)->all())
                ->lockForUpdate()->pluck('stock', 'id');

            foreach ($lines as $l) {
                if (((int) ($stocks[$l->producto->id] ?? 0)) < $l->cantidad) {
                    throw ShopException::stockInsuficiente($l->producto->nombre);
                }
            }

            $token = Str::random(48);
            $ahora = now();

            $pedidoId = DB::table('pedidos')->insertGetId([
                'nombre_cliente' => $cliente['nombre'],
                'telefono' => $cliente['telefono'],
                'email' => $cliente['email'] ?? null,
                'direccion' => $cliente['direccion'] ?? null,
                'nota' => $cliente['notas'] ?? null,
                'total' => $lines->sum(fn (CartLine $l) => $l->subtotal()),
                'token' => $token,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            DB::table('pedido_items')->insert($lines->map(fn (CartLine $l) => [
                'pedido_id' => $pedidoId,
                'producto_id' => $l->producto->id,
                'nombre' => $l->producto->nombre,               // snapshot
                'precio_unitario' => $l->producto->precio_unitario, // snapshot
                'cantidad' => $l->cantidad,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->all());

            return $token;
        });

        $this->cart->clear();

        return $token;
    }

    public function obtenerPorToken(string $token): ?object
    {
        $pedido = DB::table('pedidos')->where('token', $token)->first();

        if ($pedido) {
            $pedido->items = DB::table('pedido_items')->where('pedido_id', $pedido->id)->orderBy('id')->get();
        }

        return $pedido;
    }
}
