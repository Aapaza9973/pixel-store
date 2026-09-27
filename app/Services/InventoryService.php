<?php

namespace App\Services;

use App\Exceptions\StockInsuficienteException;
use App\Models\AlertaStock;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\StockUbicacion;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Único punto de mutación de stock del sistema.
     *
     * @param  int  $cantidad  Positivo = entrada, Negativo = salida
     * @param  string  $tipo  entrada|salida|ajuste|venta|devolucion|baja
     *
     * @throws StockInsuficienteException
     */
    public function ajustarStock(
        Producto $producto,
        int $cantidad,
        string $tipo,
        ?string $motivo = null,
        ?int $ubicacionId = null,
        ?int $userId = null
    ): MovimientoStock {
        return DB::transaction(function () use ($producto, $cantidad, $tipo, $motivo, $ubicacionId, $userId) {
            // Bloqueo pesimista: evita condiciones de carrera sobre el mismo producto.
            $productoBloqueado = Producto::where('id', $producto->id)
                ->lockForUpdate()
                ->firstOrFail();

            $stockNuevo = $productoBloqueado->stock + $cantidad;

            if ($stockNuevo < 0) {
                throw new StockInsuficienteException(
                    "Stock insuficiente para {$productoBloqueado->nombre}. "
                    ."Disponible: {$productoBloqueado->stock}, solicitado: ".abs($cantidad).'.'
                );
            }

            if ($ubicacionId !== null && $cantidad !== 0) {
                $this->aplicarEnUbicacion($productoBloqueado, $ubicacionId, $cantidad);
            }

            $productoBloqueado->stock = $stockNuevo;
            $productoBloqueado->save();

            // Mantiene coherente la instancia recibida por el llamador.
            $producto->setAttribute('stock', $stockNuevo);

            $movimiento = $this->registrarMovimiento(
                $productoBloqueado,
                $cantidad,
                $tipo,
                $stockNuevo,
                $motivo,
                $ubicacionId,
                $userId
            );

            $this->generarAlertaSiAplica($productoBloqueado, $stockNuevo);

            return $movimiento;
        });
    }

    /**
     * Mueve stock entre dos ubicaciones dentro de una sola transacción.
     */
    public function transferir(
        Producto $producto,
        int $origenId,
        int $destinoId,
        int $cantidad,
        ?int $userId = null
    ): void {
        if ($cantidad <= 0) {
            throw new StockInsuficienteException('La cantidad a transferir debe ser mayor que cero.');
        }

        DB::transaction(function () use ($producto, $origenId, $destinoId, $cantidad, $userId) {
            $nombres = Ubicacion::whereIn('id', [$origenId, $destinoId])->pluck('nombre', 'id');

            $motivo = 'Transferencia '.($nombres[$origenId] ?? "ID {$origenId}")
                .' → '.($nombres[$destinoId] ?? "ID {$destinoId}");

            $this->ajustarStock($producto, -$cantidad, 'salida', $motivo, $origenId, $userId);
            $this->ajustarStock($producto, $cantidad, 'entrada', $motivo, $destinoId, $userId);
        });
    }

    public function hayStockSuficiente(Producto $producto, int $cantidad): bool
    {
        $stock = Producto::whereKey($producto->id)->value('stock');

        return ($stock ?? $producto->stock) >= $cantidad;
    }

    /**
     * Recalcula productos.stock a partir de la suma de sus ubicaciones.
     */
    public function sincronizarStockTotal(Producto $producto): void
    {
        DB::transaction(function () use ($producto) {
            $productoBloqueado = Producto::where('id', $producto->id)
                ->lockForUpdate()
                ->firstOrFail();

            $total = (int) StockUbicacion::where('producto_id', $productoBloqueado->id)->sum('cantidad');

            $productoBloqueado->stock = $total;
            $productoBloqueado->save();

            $producto->setAttribute('stock', $total);
        });
    }

    /**
     * Genera las alertas faltantes para todos los productos en nivel crítico.
     * Idempotente: respeta la deduplicación (producto + tipo + leida = false).
     *
     * @return int Cantidad de alertas creadas
     */
    public function generarAlertasPendientes(): int
    {
        $generadas = 0;

        Producto::query()
            ->whereColumn('stock', '<=', 'umbral_alerta')
            ->chunkById(200, function ($productos) use (&$generadas) {
                foreach ($productos as $producto) {
                    if ($this->generarAlertaSiAplica($producto, (int) $producto->stock)) {
                        $generadas++;
                    }
                }
            });

        return $generadas;
    }

    /**
     * Ajusta la fila de stock_ubicacion evitando cantidades negativas.
     *
     * @throws StockInsuficienteException
     */
    private function aplicarEnUbicacion(Producto $producto, int $ubicacionId, int $cantidad): void
    {
        $stockUbicacion = StockUbicacion::where('producto_id', $producto->id)
            ->where('ubicacion_id', $ubicacionId)
            ->lockForUpdate()
            ->first();

        $cantidadActual = $stockUbicacion?->cantidad ?? 0;
        $cantidadNueva = $cantidadActual + $cantidad;

        if ($cantidadNueva < 0) {
            $nombreUbicacion = Ubicacion::whereKey($ubicacionId)->value('nombre') ?? "ID {$ubicacionId}";

            throw new StockInsuficienteException(
                "Stock insuficiente en la ubicación {$nombreUbicacion} para {$producto->nombre}. "
                ."Disponible: {$cantidadActual}, solicitado: ".abs($cantidad).'.'
            );
        }

        if ($stockUbicacion) {
            $stockUbicacion->update(['cantidad' => $cantidadNueva]);

            return;
        }

        StockUbicacion::create([
            'producto_id' => $producto->id,
            'ubicacion_id' => $ubicacionId,
            'cantidad' => $cantidadNueva,
        ]);
    }

    /**
     * Persiste el movimiento con su stock_resultante para trazabilidad.
     */
    protected function registrarMovimiento(
        Producto $producto,
        int $cantidad,
        string $tipo,
        int $stockResultante,
        ?string $motivo,
        ?int $ubicacionId,
        ?int $userId
    ): MovimientoStock {
        return MovimientoStock::create([
            'producto_id' => $producto->id,
            'ubicacion_id' => $ubicacionId,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'stock_resultante' => $stockResultante,
            'motivo' => $motivo,
            'user_id' => $userId,
        ]);
    }

    /**
     * Crea una alerta cuando el stock cruza el umbral, sin duplicar las no leídas.
     *
     * @return bool true si se creó una alerta nueva
     */
    protected function generarAlertaSiAplica(Producto $producto, int $stockNuevo): bool
    {
        $tipo = match (true) {
            $stockNuevo <= 0 => 'sin_stock',
            $stockNuevo <= $producto->umbral_alerta => 'bajo_stock',
            default => null,
        };

        if ($tipo === null) {
            return false;
        }

        $yaExiste = AlertaStock::where('producto_id', $producto->id)
            ->where('tipo', $tipo)
            ->where('leida', false)
            ->exists();

        if ($yaExiste) {
            return false;
        }

        AlertaStock::create([
            'producto_id' => $producto->id,
            'tipo' => $tipo,
            'mensaje' => "Producto {$producto->nombre} tiene {$stockNuevo} unidades (umbral: {$producto->umbral_alerta})",
            'leida' => false,
        ]);

        return true;
    }
}
