<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SerieNoDisponibleException;

/**
 * Validación y transición de estados de números de serie.
 *
 * Skeleton del Día 1. La implementación completa va en Día 2 (Jose).
 */
class NumeroSerieService
{
    /**
     * Verifica que un número de serie pueda venderse.
     *
     * Debe comprobar, dentro de la transacción de la venta:
     *   - que el número de serie existe,
     *   - que pertenece al producto indicado,
     *   - que su estado es 'disponible',
     *   - que no tiene una venta activa asociada.
     *
     * @throws SerieNoDisponibleException
     */
    public function validarParaVenta(int $numeroSerieId, int $productoId): void
    {
        throw new \RuntimeException('NumeroSerieService::validarParaVenta se implementa en Día 2 (Jose).');
    }
}
