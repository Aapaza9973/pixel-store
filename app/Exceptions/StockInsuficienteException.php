<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Stock insuficiente para completar una salida de inventario o una venta.
 */
class StockInsuficienteException extends DomainException
{
    public function getStatusCode(): int
    {
        return 409;
    }

    public function getUserMessage(): string
    {
        return 'El producto ya no está disponible';
    }

    public function getErrorCode(): string
    {
        return 'stock_insuficiente';
    }
}
