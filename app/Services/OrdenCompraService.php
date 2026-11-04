<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\OrdenCompraRecepcion;
use App\Models\OrdenCompraRecepcionItem;
use App\Models\Proveedor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Órdenes de compra (3.4.6).
 *
 * Crea la cabecera y sus líneas con los totales ya calculados. NO mueve stock
 * ni implementa la recepción (3.4.7, Día 3): la orden nace en 'borrador' y el
 * ajuste de inventario es una operación aparte.
 */
class OrdenCompraService
{
    /**
     * Calcula las líneas y los totales de una orden.
     *
     * @param  array<int, array{producto_id:int|string, cantidad:int|string, precio_unitario:float|string}>  $items
     * @return array{lineas: array<int, array{producto_id:int, cantidad:int, precio_unitario:float, subtotal:float}>, subtotal: float, impuestos: float, total: float}
     *
     * @throws DomainException
     */
    public function calcularTotales(array $items, float $impuestos = 0.0): array
    {
        if ($items === []) {
            throw new DomainException('La orden de compra necesita al menos un ítem.');
        }

        if ($impuestos < 0) {
            throw new DomainException('Los impuestos no pueden ser negativos.');
        }

        $lineas = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $productoId = (int) ($item['producto_id'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $precioUnitario = (float) ($item['precio_unitario'] ?? 0);

            if ($productoId <= 0) {
                throw new DomainException('Cada ítem debe indicar un producto.');
            }

            if ($cantidad <= 0) {
                throw new DomainException('La cantidad de cada ítem debe ser mayor que cero.');
            }

            if ($precioUnitario < 0) {
                throw new DomainException('El precio unitario no puede ser negativo.');
            }

            $lineaSubtotal = round($cantidad * $precioUnitario, 2);
            $subtotal = round($subtotal + $lineaSubtotal, 2);

            $lineas[] = [
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
                'precio_unitario' => round($precioUnitario, 2),
                'subtotal' => $lineaSubtotal,
            ];
        }

        $impuestos = round($impuestos, 2);

        return [
            'lineas' => $lineas,
            'subtotal' => $subtotal,
            'impuestos' => $impuestos,
            'total' => round($subtotal + $impuestos, 2),
        ];
    }

    /**
     * Crea una orden en estado borrador con su correlativo del año.
     *
     * @param  array<int, array{producto_id:int|string, cantidad:int|string, precio_unitario:float|string}>  $items
     * @param  array{fecha_emision?: string, fecha_entrega?: string|null, impuestos?: float|string, notas?: string|null}  $datos
     *
     * @throws DomainException
     */
    // Falta el paso "enviar orden" (pasar de 'borrador' a 'enviada'); los
    // tests lo fijan con un update directo. Se implementa en Día 4.
    public function crear(Proveedor $proveedor, User $user, array $items, array $datos = []): OrdenCompra
    {
        if ($items === []) {
            throw new DomainException('La orden de compra necesita al menos un ítem.');
        }

        return DB::transaction(function () use ($proveedor, $user, $items, $datos) {
            $totales = $this->calcularTotales($items, (float) ($datos['impuestos'] ?? 0));

            $fechaEmision = $datos['fecha_emision'] ?? now()->toDateString();
            $anio = (int) CarbonImmutable::parse($fechaEmision)->year;

            $orden = OrdenCompra::create([
                'numero' => $this->siguienteNumero($anio),
                'proveedor_id' => $proveedor->id,
                'user_id' => $user->id,
                'fecha_emision' => $fechaEmision,
                'fecha_entrega' => $datos['fecha_entrega'] ?? null,
                'estado' => 'borrador',
                'subtotal' => $totales['subtotal'],
                'impuestos' => $totales['impuestos'],
                'total' => $totales['total'],
                'notas' => $datos['notas'] ?? null,
            ]);

            foreach ($totales['lineas'] as $linea) {
                $orden->detalles()->create($linea);
            }

            return $orden->load('detalles.producto');
        });
    }

    /**
     * Registra una recepción (parcial o total) de la orden y suma el stock de
     * cada línea recibida (3.4.7).
     *
     * Solo se puede recibir una orden ya enviada al proveedor ('enviada') o con
     * recepciones previas ('recibida_parcial'). Un 'borrador' no se pidió, así
     * que no tiene nada que recibir.
     *
     * @param  array<int, array{orden_compra_item_id:int|string, cantidad:int|string}>  $items
     * @param  array{notas?: string|null}  $datos
     *
     * @throws DomainException
     */
    public function recibir(OrdenCompra $orden, User $user, array $items, array $datos = []): OrdenCompraRecepcion
    {
        if (! in_array($orden->estado, ['enviada', 'recibida_parcial'], true)) {
            throw new DomainException('Solo se pueden recibir órdenes enviadas o recibidas parcialmente.');
        }

        if ($items === []) {
            throw new DomainException('La recepción necesita al menos un ítem.');
        }

        return DB::transaction(function () use ($orden, $user, $items, $datos) {
            // Bloquea la orden para que dos recepciones simultáneas no superen lo pedido.
            $orden = OrdenCompra::whereKey($orden->id)->lockForUpdate()->firstOrFail();

            $inventario = app(InventoryService::class);
            $lineas = [];

            foreach ($items as $item) {
                $itemId = (int) ($item['orden_compra_item_id'] ?? 0);
                $cantidad = (int) ($item['cantidad'] ?? 0);

                if ($cantidad <= 0) {
                    throw new DomainException('La cantidad a recibir debe ser mayor que cero.');
                }

                $linea = OrdenCompraDetalle::where('orden_compra_id', $orden->id)
                    ->whereKey($itemId)
                    ->first();

                if ($linea === null) {
                    throw new DomainException('Una de las líneas no pertenece a esta orden.');
                }

                $pendiente = (int) $linea->cantidad - $this->recibidoDe($linea);

                if ($cantidad > $pendiente) {
                    $producto = $linea->producto?->nombre ?? 'el producto';

                    $frase = $pendiente === 1
                        ? 'Solo falta 1 unidad'
                        : "Solo faltan {$pendiente} unidades";

                    throw new DomainException("{$frase} por recibir de {$producto}.");
                }

                $lineas[] = ['linea' => $linea, 'cantidad' => $cantidad];
            }

            $recepcion = OrdenCompraRecepcion::create([
                'orden_id' => $orden->id,
                'user_id' => $user->id,
                'fecha_recepcion' => now(),
                'notas' => $datos['notas'] ?? null,
            ]);

            foreach ($lineas as ['linea' => $linea, 'cantidad' => $cantidad]) {
                OrdenCompraRecepcionItem::create([
                    'recepcion_id' => $recepcion->id,
                    'orden_compra_item_id' => $linea->id,
                    'cantidad' => $cantidad,
                ]);

                $inventario->ajustarStock(
                    $linea->producto,
                    $cantidad,
                    'entrada',
                    "Recepción OC {$orden->numero}",
                    null,
                    $user->id
                );
            }

            $this->actualizarEstado($orden);

            return $recepcion->load('items.ordenCompraItem.producto');
        });
    }

    /**
     * Unidades ya recibidas de una línea, sumando todas las recepciones.
     */
    public function recibidoDe(OrdenCompraDetalle $item): int
    {
        return (int) OrdenCompraRecepcionItem::where('orden_compra_item_id', $item->id)->sum('cantidad');
    }

    /**
     * 'recibida_total' si todas las líneas están completas; 'recibida_parcial' si
     * alguna tiene unidades recibidas.
     */
    private function actualizarEstado(OrdenCompra $orden): void
    {
        $lineas = $orden->detalles()->get();

        $todasCompletas = $lineas->every(fn (OrdenCompraDetalle $linea) => $this->recibidoDe($linea) >= (int) $linea->cantidad);
        $algunaRecibida = $lineas->contains(fn (OrdenCompraDetalle $linea) => $this->recibidoDe($linea) > 0);

        if ($todasCompletas) {
            $orden->update(['estado' => 'recibida_total']);
        } elseif ($algunaRecibida) {
            $orden->update(['estado' => 'recibida_parcial']);
        }
    }

    /**
     * Siguiente número OC-YYYY-NNNN del año.
     *
     * El advisory lock es de transacción y serializa a los usuarios que crean
     * el primer pedido del año a la vez; sin él, dos MAX simultáneos generan
     * el mismo número y el UNIQUE devuelve 500.
     */
    private function siguienteNumero(int $anio): string
    {
        $prefijo = 'OC-'.$anio.'-';

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', ['ordenes_compra:'.$anio]);
        }

        $ultimo = OrdenCompra::query()
            ->where('numero', 'like', $prefijo.'%')
            ->max('numero');

        $secuencia = $ultimo === null
            ? 1
            : (int) substr((string) $ultimo, strlen($prefijo)) + 1;

        return $prefijo.str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT);
    }
}
