<?php

namespace App\DTOs;

/** Una línea del carrito. $producto es la fila pública del catálogo (stdClass). */
final class CartLine
{
    public function __construct(
        public readonly object $producto,
        public readonly int $cantidad,
    ) {}

    public function subtotal(): float
    {
        return round((float) $this->producto->precio_unitario * $this->cantidad, 2);
    }
}
