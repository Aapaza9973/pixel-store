<?php

namespace App\Exceptions;

use RuntimeException;

class ShopException extends RuntimeException
{
    public static function stockInsuficiente(string $nombre): self
    {
        return new self("No hay stock suficiente de «{$nombre}». Ajusta la cantidad.");
    }

    public static function productoNoDisponible(): self
    {
        return new self('El producto ya no está disponible.');
    }

    public static function carritoVacio(): self
    {
        return new self('Tu carrito está vacío.');
    }
}
