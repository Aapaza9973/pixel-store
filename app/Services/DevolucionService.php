<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\DetalleVenta;
use App\Models\Devolucion;
use App\Models\DevolucionDetalle;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Registro de devoluciones (HU-5.14).
 *
 * Una devolución es una cabecera (`devoluciones`) con una línea por producto
 * devuelto (`devolucion_detalles`). Todo ocurre en una sola transacción: la
 * cabecera, el reingreso de stock, la liberación del número de serie y el
 * estado de la venta.
 */
class DevolucionService
{
    public function __construct(private readonly InventoryService $inventario) {}

    /**
     * Registra una devolución validando que no se devuelva más de lo vendido.
     *
     * @param  array<int, array{venta_detalle_id:int, cantidad:int}>  $items
     *
     * @throws DomainException
     */
    public function registrar(
        int $ventaId,
        array $items,
        string $motivo,
        string $operacionId,
        int $userId
    ): Devolucion {
        return DB::transaction(function () use ($ventaId, $items, $motivo, $operacionId, $userId) {
            // Idempotencia mínima (5.14.10 la completa): operacion_id es UNIQUE,
            // así que el guard evita el 500 en el reintento del POS.
            $existente = Devolucion::where('operacion_id', $operacionId)->first();

            if ($existente !== null) {
                return $existente->load('detalles');
            }

            $venta = Venta::whereKey($ventaId)->first();

            if ($venta === null) {
                throw new DomainException('La venta indicada no existe.');
            }

            if ($venta->estado === 'Cancelada') {
                throw new DomainException('No se puede devolver una venta cancelada.');
            }

            $lineas = [];
            $totalDevuelto = 0.0;

            // Lo pedido en ESTA devolución por línea: dos items del mismo
            // venta_detalle_id no deben poder superar juntos lo vendido.
            $pedidas = [];

            foreach ($items as $item) {
                $ventaDetalleId = (int) ($item['venta_detalle_id'] ?? 0);
                $cantidad = (int) ($item['cantidad'] ?? 0);

                if ($cantidad <= 0) {
                    throw new DomainException('La cantidad a devolver debe ser mayor que cero.');
                }

                $detalle = DetalleVenta::whereKey($ventaDetalleId)->first();

                if ($detalle === null || (int) $detalle->venta_id !== (int) $venta->id) {
                    throw new DomainException('La línea de venta no pertenece a la venta indicada.');
                }

                $pedidas[$detalle->id] = ($pedidas[$detalle->id] ?? 0) + $cantidad;
                $vendidas = (int) $detalle->cantidad;
                $devueltas = $this->cantidadDevuelta((int) $detalle->id);

                if ($vendidas < $pedidas[$detalle->id] + $devueltas) {
                    $disponibles = max($vendidas - $devueltas, 0);
                    $nombre = $detalle->producto?->nombre ?? "producto #{$detalle->producto_id}";
                    $frase = $disponibles === 1
                        ? 'Solo queda 1 unidad'
                        : "Solo quedan {$disponibles} unidades";

                    throw new DomainException("{$frase} por devolver de {$nombre}.");
                }

                $monto = round($cantidad * (float) $detalle->precio_unitario, 2);
                $totalDevuelto += $monto;

                $lineas[] = [
                    'venta_detalle_id' => $detalle->id,
                    'venta_id' => $venta->id,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $cantidad,
                    'monto' => $monto,
                ];
            }

            $devolucion = Devolucion::create([
                'operacion_id' => $operacionId,
                'venta_id' => $venta->id,
                'user_id' => $userId,
                'motivo' => $motivo,
                'estado' => 'procesada',
                'total_devuelto' => round($totalDevuelto, 2),
            ]);

            foreach ($lineas as $linea) {
                DevolucionDetalle::create([
                    'devolucion_id' => $devolucion->id,
                    'venta_detalle_id' => $linea['venta_detalle_id'],
                    'venta_id' => $linea['venta_id'],
                    'producto_id' => $linea['producto_id'],
                    'cantidad' => $linea['cantidad'],
                    // La línea exige motivo; el de la cabecera es el que el
                    // usuario escribió, hasta que 5.15 permita uno por línea.
                    'motivo' => $motivo,
                    'estado' => 'Pendiente',
                    'monto' => $linea['monto'],
                ]);
            }

            $this->reingresarStock($devolucion, $userId);
            $this->actualizarEstadoVenta($venta, $devolucion);

            return $devolucion->load('detalles');
        });
    }

    /**
     * 5.14.6 y 5.14.7: devuelve al stock cada línea y libera su número de serie.
     */
    private function reingresarStock(Devolucion $devolucion, int $userId): void
    {
        foreach ($devolucion->detalles()->with('ventaDetalle.numeroSerie')->get() as $linea) {
            $producto = Producto::whereKey($linea->producto_id)->firstOrFail();

            $this->inventario->ajustarStock(
                $producto,
                (int) $linea->cantidad,
                'devolucion',
                "Devolución #{$devolucion->id}",
                null,
                $userId
            );

            $serie = $linea->ventaDetalle?->numeroSerie;

            if ($serie !== null) {
                $serie->update([
                    'estado' => 'disponible',
                    'venta_id' => null,
                    'fecha_venta' => null,
                    'cliente_id' => null,
                ]);
            }
        }
    }

    /**
     * 5.15.3: 'totalmente_devuelta' si lo devuelto cubre el total; si no, 'parcialmente_devuelta'.
     */
    private function actualizarEstadoVenta(Venta $venta, Devolucion $devolucion): void
    {
        $devuelto = (float) Devolucion::query()
            ->where('venta_id', $venta->id)
            ->where('estado', '!=', 'anulada')
            ->sum('total_devuelto');

        $venta->update([
            'estado' => $devuelto >= (float) $venta->total - 0.01
                ? 'totalmente_devuelta'
                : 'parcialmente_devuelta',
        ]);
    }

    /**
     * Unidades que todavía se pueden devolver por línea de la venta, indexadas
     * por `venta_detalle_id`. Lo usa la vista de detalle para el modal.
     *
     * @return array<int, int>
     */
    public function pendientesPorLinea(Venta $venta): array
    {
        $pendientes = [];

        foreach ($venta->detalles as $detalle) {
            $pendientes[$detalle->id] = max(0, (int) $detalle->cantidad - $this->cantidadDevuelta($detalle->id));
        }

        return $pendientes;
    }

    /**
     * Unidades ya devueltas de una línea de venta, sin contar las devoluciones
     * anuladas: una devolución anulada libera esas unidades para devolver.
     */
    private function cantidadDevuelta(int $ventaDetalleId): int
    {
        return (int) DevolucionDetalle::query()
            ->join('devoluciones', 'devoluciones.id', '=', 'devolucion_detalles.devolucion_id')
            ->where('devolucion_detalles.venta_detalle_id', $ventaDetalleId)
            ->where('devoluciones.estado', '!=', 'anulada')
            ->sum('devolucion_detalles.cantidad');
    }
}
