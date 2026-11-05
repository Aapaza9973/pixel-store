<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SerieNoDisponibleException;
use App\Models\NumeroSerie;

/**
 * Validación y transición de estados de números de serie.
 *
 * Estados canónicos (enum_numero_serie_estado):
 * disponible, vendido, reservado, devuelto, defectuoso, baja.
 */
class NumeroSerieService
{
    /**
     * Verifica que un número de serie pueda venderse.
     *
     * Debe llamarse dentro de la transacción de la venta (R-VD-RF-07): bloquea
     * la fila para que dos ventas simultáneas no se queden con la misma serie.
     *
     * @throws SerieNoDisponibleException
     */
    public function validarParaVenta(int $numeroSerieId, int $productoId): NumeroSerie
    {
        $numeroSerie = NumeroSerie::query()
            ->lockForUpdate()
            ->find($numeroSerieId);

        if ($numeroSerie === null) {
            throw new SerieNoDisponibleException('El número de serie no existe.');
        }

        if ((int) $numeroSerie->producto_id !== $productoId) {
            throw new SerieNoDisponibleException('El número de serie no corresponde al producto.');
        }

        if ($numeroSerie->estado !== 'disponible') {
            throw new SerieNoDisponibleException('El número de serie ya fue vendido.');
        }

        if ($numeroSerie->venta_id !== null) {
            throw new SerieNoDisponibleException('El número de serie ya fue vendido.');
        }

        return $numeroSerie;
    }
}
