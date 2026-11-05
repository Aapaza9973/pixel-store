<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Cotizacion;
use App\Models\User;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Lógica de negocio de cotizaciones (HU Fase 2.1).
 */
class CotizacionService
{
    /**
     * Crea una cotización con sus ítems, calculando totales.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, array<string, mixed>>  $items
     */
    public function crear(array $datos, array $items, User $user): Cotizacion
    {
        return DB::transaction(function () use ($datos, $items, $user) {
            $subtotal = collect($items)->sum(
                fn (array $i): float => (float) $i['precio_unitario'] * (int) $i['cantidad']
            );
            $descuento = (float) ($datos['descuento'] ?? 0);
            $validezDias = (int) ($datos['validez_dias'] ?? 4);

            $cotizacion = Cotizacion::create([
                'numero' => $this->generarNumero(),
                'cliente_id' => $datos['cliente_id'] ?? null,
                'user_id' => $user->id,
                'nombre_cliente' => $datos['nombre_cliente'],
                'nit_ci' => $datos['nit_ci'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'email' => $datos['email'] ?? null,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => max(0, $subtotal - $descuento),
                'validez_dias' => $validezDias,
                'fecha_emision' => now(),
                'fecha_vencimiento' => now()->addDays($validezDias),
                'estado' => 'Vigente',
                'notas' => $datos['notas'] ?? null,
            ]);

            foreach ($items as $item) {
                $cotizacion->items()->create([
                    'producto_id' => $item['producto_id'] ?? null,
                    'nombre' => $item['nombre'],
                    'precio_unitario' => $item['precio_unitario'],
                    'cantidad' => $item['cantidad'],
                ]);
            }

            return $cotizacion;
        });
    }

    /**
     * Genera el PDF de la cotización (binario).
     */
    public function generarPdf(Cotizacion $cotizacion): string
    {
        $cotizacion->load(['items.producto', 'vendedor', 'cliente']);

        return Pdf::loadView('admin.cotizaciones.pdf', ['cotizacion' => $cotizacion])->output();
    }

    /**
     * Anula una cotización vigente.
     */
    public function anular(Cotizacion $cotizacion): void
    {
        $cotizacion->update(['estado' => 'Anulada']);
    }

    /**
     * Convierte la cotización en una venta (estado Pendiente) y la marca como
     * Convertida. Solo se convierten los ítems con producto asociado.
     *
     * @throws DomainException si ningún ítem tiene producto asociado
     */
    public function convertirAVenta(Cotizacion $cotizacion, User $user): Venta
    {
        return DB::transaction(function () use ($cotizacion, $user) {
            $cotizacion->load('items');

            $items = $cotizacion->items->filter(fn ($i) => $i->producto_id !== null);

            if ($items->isEmpty()) {
                throw new DomainException(
                    'No se puede convertir la cotización: ningún ítem tiene producto asociado.'
                );
            }

            $venta = Venta::create([
                'operacion_id' => (string) Str::uuid(),
                'fecha' => now(),
                'user_id' => $user->id,
                'cliente_id' => $cotizacion->cliente_id,
                'total' => $cotizacion->total,
                'descuento' => $cotizacion->descuento,
                'estado' => 'Pendiente',
                'cotizacion_id' => $cotizacion->id,
            ]);

            foreach ($items as $item) {
                $venta->detalles()->create([
                    'producto_id' => $item->producto_id,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'descuento_item' => 0,
                ]);
            }

            $cotizacion->update(['estado' => 'Convertida']);

            return $venta;
        });
    }

    /**
     * Marca como Vencidas las cotizaciones vigentes cuya fecha ya pasó.
     *
     * @return int cantidad de cotizaciones actualizadas
     */
    public function vencerVencidas(): int
    {
        return Cotizacion::query()
            ->where('estado', 'Vigente')
            ->where('fecha_vencimiento', '<', now())
            ->update(['estado' => 'Vencida']);
    }

    /**
     * Genera un número secuencial anual: COT-AAAA-NNNN.
     */
    private function generarNumero(): string
    {
        $anio = now()->format('Y');
        $ultimo = Cotizacion::query()
            ->where('numero', 'LIKE', "COT-{$anio}-%")
            ->orderByDesc('id')
            ->value('numero');

        $secuencia = $ultimo ? ((int) substr((string) $ultimo, -4)) + 1 : 1;

        return sprintf('COT-%s-%04d', $anio, $secuencia);
    }
}
