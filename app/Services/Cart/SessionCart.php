<?php

namespace App\Services\Cart;

use App\Contracts\CartInterface;
use App\Contracts\ProductoRepositoryInterface;
use App\DTOs\CartLine;
use App\Exceptions\ShopException;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/** Carrito provisional guardado en sesión: [producto_id => cantidad]. */
final class SessionCart implements CartInterface
{
    private const KEY = 'shop.cart';

    public function __construct(
        private readonly Session $session,
        private readonly ProductoRepositoryInterface $productos,
    ) {}

    public function add(int $productoId, int $cantidad = 1): void
    {
        $actual = $this->items()[$productoId] ?? 0;
        $this->fijar($productoId, $actual + max(1, $cantidad));
    }

    public function update(int $productoId, int $cantidad): void
    {
        $cantidad <= 0 ? $this->remove($productoId) : $this->fijar($productoId, $cantidad);
    }

    public function remove(int $productoId): void
    {
        $items = $this->items();
        unset($items[$productoId]);
        $this->session->put(self::KEY, $items);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    public function lines(): Collection
    {
        $items = $this->items();
        if ($items === []) {
            return collect();
        }

        $productos = $this->productos->buscarPorIds(array_keys($items))->keyBy('id');

        return collect($items)
            ->filter(fn ($c, $id) => $productos->has($id))
            ->map(fn ($c, $id) => new CartLine($productos[$id], (int) $c))
            ->values();
    }

    public function count(): int
    {
        return array_sum($this->items());
    }

    public function total(): float
    {
        return round($this->lines()->sum(fn (CartLine $l) => $l->subtotal()), 2);
    }

    private function items(): array
    {
        return $this->session->get(self::KEY, []);
    }

    /** Valida existencia y limita la cantidad al stock disponible. */
    private function fijar(int $productoId, int $cantidad): void
    {
        $producto = $this->productos->buscarPorIds([$productoId])->first();

        if (! $producto || $producto->stock < 1) {
            throw ShopException::productoNoDisponible();
        }

        $items = $this->items();
        $items[$productoId] = min($cantidad, (int) $producto->stock);
        $this->session->put(self::KEY, $items);
    }
}
