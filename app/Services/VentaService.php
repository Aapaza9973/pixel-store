<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PagoInsuficienteException;
use App\Exceptions\SerieNoDisponibleException;
use App\Exceptions\StockInsuficienteException;
use App\Models\Venta;

/**
 * Servicio de confirmación de ventas.
 *
 * ⚠️ CRÍTICO (Riesgo R-VD): toda confirmación debe cumplir los 6 requerimientos:
 *   R-VD-RF-01: DB::transaction único para toda la operación
 *   R-VD-RF-02: lockForUpdate ordenado por producto_id
 *   R-VD-RF-03: validación de stock bajo el mismo lock
 *   R-VD-RF-04: excepciones de dominio → 409/422 (nunca 500)
 *   R-VD-RF-06: idempotencia por operacion_id
 *   R-VD-RF-07: validación del número de serie dentro de la transacción
 *
 * Tablas canónicas del schema:
 *   - ventas (con operacion_id)
 *   - detalle_ventas (no "venta_detalle")
 *   - pagos (no "venta_pagos"), metodo en Efectivo/Tarjeta/Transferencia/QR/Otro
 *
 * La lógica completa se implementa en Día 4-5 (HU-5.13).
 */
class VentaService
{
    /**
     * Confirma una venta de forma atómica e idempotente.
     *
     * Flujo previsto (Día 4-5):
     *   1. Abrir una única DB::transaction.
     *   2. Si existe una venta con el mismo operacion_id, devolverla (idempotencia).
     *   3. Bloquear los productos implicados con lockForUpdate ordenados por producto_id.
     *   4. Validar stock de cada producto bajo el lock.
     *   5. Validar cada número de serie (NumeroSerieService) dentro de la transacción.
     *   6. Crear venta + detalle_ventas + pagos, descontar stock vía InventoryService.
     *   7. Validar que la suma de pagos cubre el total.
     *
     * @param  array<int, array{producto_id:int, cantidad:int, numero_serie_id:?int}>  $items
     * @param  array<int, array{metodo:'Efectivo'|'Tarjeta'|'Transferencia'|'QR'|'Otro', monto:float, referencia:?string}>  $pagos
     * @param  array{cliente_id:?int, vendedor_id:int, operacion_id:string}  $meta
     *
     * @throws StockInsuficienteException
     * @throws SerieNoDisponibleException
     * @throws PagoInsuficienteException
     */
    public function confirmar(array $items, array $pagos, array $meta): Venta
    {
        throw new \RuntimeException('VentaService::confirmar se implementa en Día 4-5 (HU-5.13).');
    }
}
