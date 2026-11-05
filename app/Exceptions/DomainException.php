<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Base de las excepciones de dominio del negocio.
 *
 * Permite que la capa HTTP traduzca un fallo de negocio a un status y un
 * mensaje estables, sin exponer detalles internos al usuario.
 */
class DomainException extends \DomainException
{
    public function getStatusCode(): int
    {
        return 422;
    }

    public function getUserMessage(): string
    {
        return $this->getMessage();
    }

    /**
     * Código estable para clientes/API (nunca usar Exception::getCode: es int).
     */
    public function getErrorCode(): string
    {
        return 'domain_error';
    }
}
