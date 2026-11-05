<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * El número de serie no existe, no corresponde al producto o ya fue vendido.
 */
class SerieNoDisponibleException extends DomainException
{
    public function getStatusCode(): int
    {
        return 409;
    }

    public function getUserMessage(): string
    {
        return 'El número de serie no está disponible';
    }

    public function getErrorCode(): string
    {
        return 'serie_no_disponible';
    }
}
