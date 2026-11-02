<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use App\Exceptions\PagoInsuficienteException;
use App\Exceptions\SerieNoDisponibleException;
use App\Exceptions\StockInsuficienteException;
use App\Models\DetalleVenta;
use App\Models\LogAuditoria;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

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
 */
class VentaService
{
    public function __construct(
        private readonly NumeroSerieService $numerosSerie,
        private readonly InventoryService $inventario,
    ) {}

    /**
     * Confirma una venta de forma atómica e idempotente.
     *
     * El descuento llega como porcentaje (`descuento_porcentaje`): el monto del
     * descuento y el total se recalculan acá con los precios de la base.
     *
     * @param  array<int, array{producto_id:int, cantidad:int, numero_serie_id?:?int, ubicacion_id?:?int}>  $items
     * @param  array<int, array{metodo:'Efectivo'|'Tarjeta'|'Transferencia'|'QR'|'Otro', monto:float, referencia?:?string}>  $pagos
     * @param  array{cliente_id?:?int, vendedor_id:int, operacion_id:string, descuento_porcentaje?:float|int|string, autorizado_por?:?int}  $meta
     *
     * @throws StockInsuficienteException
     * @throws SerieNoDisponibleException
     * @throws PagoInsuficienteException
     * @throws DomainException
     */
    public function confirmar(array $items, array $pagos, array $meta): Venta
    {
        // R-VD-RF-06: idempotencia fuera de la transacción (early return).
        $operacionId = (string) ($meta['operacion_id'] ?? '');

        if ($operacionId !== '') {
            $existente = Venta::where('operacion_id', $operacionId)->first();

            if ($existente !== null) {
                return $existente->load(['detalles', 'pagos']);
            }
        }

        $this->validarEntrada($items, $pagos);

        // R-VD-RF-01: una única transacción para toda la operación.
        return DB::transaction(function () use ($items, $pagos, $meta, $operacionId) {
            // R-VD-RF-02: bloqueo pesimista en orden estable por producto_id
            // para que dos ventas concurrentes no se bloqueen en cruz.
            $productos = $this->bloquearProductos($items);

            $cantidades = $this->cantidadesPorProducto($items);

            // R-VD-RF-03: la validación ocurre bajo el mismo lock que la escritura.
            foreach ($cantidades as $productoId => $cantidad) {
                $producto = $productos[$productoId];

                if ($producto->stock < $cantidad) {
                    throw new StockInsuficienteException(
                        "Stock insuficiente para {$producto->nombre}. "
                        ."Disponible: {$producto->stock}, solicitado: {$cantidad}."
                    );
                }
            }

            // El descuento llega como porcentaje (nunca como monto): así el
            // cliente no puede alterar la base de cálculo.
            $porcentaje = round((float) ($meta['descuento_porcentaje'] ?? 0), 2);
            $subtotal = $this->calcularTotal($items, $productos);
            $descuento = round($subtotal * $porcentaje / 100, 2);
            $total = round(max($subtotal - $descuento, 0), 2);

            // R-VD-RF-04: falla de negocio, nunca 500.
            // Se acepta pago de más: `suma(pagos) >= total` es válido porque en
            // efectivo puede haber vuelto. Solo se rechaza cuando falta dinero.
            $totalPagado = round(array_sum(array_map(
                static fn (array $pago): float => (float) $pago['monto'],
                $pagos
            )), 2);

            if ($totalPagado + 0.001 < $total) {
                throw new PagoInsuficienteException(
                    'El monto pagado no cubre el total de la venta. '
                    ."Total: {$total}, pagado: {$totalPagado}."
                );
            }

            // R-VD-RF-07: los números de serie se validan dentro de la transacción.
            $series = $this->validarSeries($items);

            $venta = Venta::create([
                'operacion_id' => $operacionId !== '' ? $operacionId : null,
                'fecha' => now(),
                'user_id' => $meta['vendedor_id'],
                'cliente_id' => $meta['cliente_id'] ?? null,
                'total' => $total,
                'descuento' => $descuento,
                'descuento_porcentaje' => $porcentaje,
                'autorizado_por' => $meta['autorizado_por'] ?? null,
                'estado' => 'Pagado',
            ]);

            foreach ($items as $item) {
                $producto = $productos[$item['producto_id']];

                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto->id,
                    'numero_serie_id' => $item['numero_serie_id'] ?? null,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio_unitario,
                ]);
            }

            foreach ($pagos as $pago) {
                Pago::create([
                    'venta_id' => $venta->id,
                    'monto' => $pago['monto'],
                    'metodo' => $pago['metodo'],
                    'estado' => 'Completado',
                    'referencia' => $pago['referencia'] ?? null,
                ]);
            }

            // El stock se descuenta después de persistir la venta para que el
            // motivo del movimiento pueda referenciar el comprobante.
            foreach ($items as $item) {
                $this->inventario->ajustarStock(
                    $productos[$item['producto_id']],
                    -(int) $item['cantidad'],
                    'venta',
                    "Venta #{$venta->id}",
                    $item['ubicacion_id'] ?? null,
                    $meta['vendedor_id']
                );
            }

            foreach ($series as $serie) {
                $serie->update([
                    'estado' => 'vendido',
                    'venta_id' => $venta->id,
                    'cliente_id' => $meta['cliente_id'] ?? null,
                    'fecha_venta' => now(),
                ]);
            }

            LogAuditoria::create([
                'user_id' => $meta['vendedor_id'],
                'accion' => 'crear_venta',
                'modelo' => Venta::class,
                'modelo_id' => $venta->id,
                'datos_anteriores' => null,
                'datos_nuevos' => [
                    'total' => $total,
                    'descuento' => $descuento,
                    'descuento_porcentaje' => $porcentaje,
                    'operacion_id' => $operacionId !== '' ? $operacionId : null,
                    'cliente_id' => $meta['cliente_id'] ?? null,
                    'items' => count($items),
                    'pagos' => count($pagos),
                ],
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $venta->load(['detalles', 'pagos']);
        });
    }

    /**
     * Bloquea los productos implicados en orden ascendente de id.
     *
     * @param  array<int, array{producto_id:int}>  $items
     * @return array<int, Producto>
     */
    private function bloquearProductos(array $items): array
    {
        $ids = collect($items)
            ->pluck('producto_id')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $productos = [];

        foreach ($ids as $id) {
            $producto = Producto::whereKey($id)->lockForUpdate()->first();

            if ($producto === null) {
                throw new StockInsuficienteException('El producto solicitado no existe.');
            }

            $productos[(int) $id] = $producto;
        }

        return $productos;
    }

    /**
     * Agrupa las cantidades por producto: una venta puede traer dos líneas del
     * mismo producto y el stock debe validarse contra la suma.
     *
     * @param  array<int, array{producto_id:int, cantidad:int}>  $items
     * @return array<int, int>
     */
    private function cantidadesPorProducto(array $items): array
    {
        $cantidades = [];

        foreach ($items as $item) {
            $productoId = (int) $item['producto_id'];
            $cantidades[$productoId] = ($cantidades[$productoId] ?? 0) + (int) $item['cantidad'];
        }

        return $cantidades;
    }

    /**
     * @param  array<int, array{producto_id:int, cantidad:int}>  $items
     * @param  array<int, Producto>  $productos
     */
    private function calcularTotal(array $items, array $productos): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $precio = (float) $productos[(int) $item['producto_id']]->precio_unitario;
            $total += $precio * (int) $item['cantidad'];
        }

        return round($total, 2);
    }

    /**
     * @param  array<int, array{producto_id:int, numero_serie_id?:?int}>  $items
     * @return array<int, \App\Models\NumeroSerie>
     */
    private function validarSeries(array $items): array
    {
        $series = [];

        foreach ($items as $item) {
            if (empty($item['numero_serie_id'])) {
                continue;
            }

            $series[] = $this->numerosSerie->validarParaVenta(
                (int) $item['numero_serie_id'],
                (int) $item['producto_id']
            );
        }

        return $series;
    }

    /**
     * Restricciones de forma: cantidades y montos estrictamente positivos.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $pagos
     */
    private function validarEntrada(array $items, array $pagos): void
    {
        if ($items === []) {
            throw new DomainException('La venta debe incluir al menos un producto.');
        }

        foreach ($items as $item) {
            if ((int) ($item['cantidad'] ?? 0) <= 0) {
                throw new DomainException('La cantidad de cada producto debe ser mayor que cero.');
            }
        }

        if ($pagos === []) {
            throw new PagoInsuficienteException('La venta debe registrar al menos un pago.');
        }

        foreach ($pagos as $pago) {
            if ((float) ($pago['monto'] ?? 0) <= 0) {
                throw new PagoInsuficienteException('El monto de cada pago debe ser mayor que cero.');
            }
        }
    }
}
