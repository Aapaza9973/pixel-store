<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;

/**
 * Reglas de descuento por rol (HU-5.10).
 *
 * El límite vive en `roles.descuento_maximo` y se expresa en porcentaje sobre
 * la base de la venta. La autorización de un administrador para superar el
 * límite (HU-5.11) queda para el Sprint 3.
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
        $rolObj = is_string($rol) ? Role::findByName($rol) : $rol;

        // Sin base no hay porcentaje que comparar.
        if ($montoBase <= 0) {
            return true;
        }

        if ($montoDescuento <= 0) {
            return true;
        }

        $maximo = (float) $rolObj->descuento_maximo;
        $porcentaje = ($montoDescuento / $montoBase) * 100;

        // Tolerancia para no rechazar por redondeo de centavos.
        return $porcentaje <= $maximo + 0.0001;
    }
}
