<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;

/**
 * Reglas de descuento por rol.
 *
 * Skeleton del Día 1. La implementación completa va en Día 2 (Brandon).
 */
class DescuentoService
{
    /**
     * Indica si el descuento pedido está dentro del límite del rol.
     *
     * Criterio: montoDescuento <= montoBase * (descuento_maximo / 100).
     *
     * @param  Role|string  $rol  Rol o nombre del rol del vendedor
     * @param  float  $montoDescuento  Descuento en bolivianos
     * @param  float  $montoBase  Base sobre la que se aplica el descuento
     */
    public function validar(Role|string $rol, float $montoDescuento, float $montoBase): bool
    {
        throw new \RuntimeException('DescuentoService::validar se implementa en Día 2 (Brandon).');
    }
}
