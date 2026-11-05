<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * La suma de los pagos no alcanza el total de la venta.
 */
class PagoInsuficienteException extends DomainException
{
    public function getStatusCode(): int
    {
        return 422;
    }

    public function getUserMessage(): string
    {
        return 'El monto pagado no cubre el total de la venta';
    }

    public function getErrorCode(): string
    {
        return 'pago_insuficiente';
    }
}
