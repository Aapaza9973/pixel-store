<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\FacturaElectronica;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Facturación electrónica (HU-4.1).
 *
 * **Modo simulación**: genera número y CUF localmente y guarda un XML con los
 * datos de la venta. La firma digital y el envío al SIN (CUCU / Libélula)
 * requieren credenciales del proveedor; cuando existan, solo se reemplazan
 * `generarCuf()` y `enviarAlSin()` sin cambiar el contrato.
 */
class FacturacionService
{
    /**
     * Emite la factura de una venta cobrada.
     *
     * @throws DomainException Si la venta no está cobrada o ya tiene factura.
     */
    public function emitir(Venta $venta, ?int $userId = null): FacturaElectronica
    {
        return DB::transaction(function () use ($venta, $userId) {
            $bloqueada = Venta::whereKey($venta->id)->lockForUpdate()->firstOrFail();

            if (! in_array($bloqueada->estado, ReporteService::ESTADOS_COBRADOS, true)) {
                throw new DomainException('Solo se factura una venta cobrada.');
            }

            if (FacturaElectronica::where('venta_id', $bloqueada->id)->exists()) {
                throw new DomainException('La venta ya tiene una factura emitida.');
            }

            return FacturaElectronica::create([
                'venta_id' => $bloqueada->id,
                'user_id' => $userId,
                'numero_factura' => $this->siguienteNumero(),
                'cuf' => $this->generarCuf($bloqueada),
                'cufd' => $this->generarCuf($bloqueada),
                'estado' => 'Emitida',
                'fecha_emision' => now(),
                'xml' => $this->xmlSimulado($bloqueada),
            ]);
        });
    }

    /**
     * Anula una factura emitida. La venta no se toca: la anulación es
     * tributaria, no comercial.
     *
     * @throws DomainException
     */
    public function anular(FacturaElectronica $factura, string $motivo): FacturaElectronica
    {
        return DB::transaction(function () use ($factura, $motivo) {
            $bloqueada = FacturaElectronica::whereKey($factura->id)->lockForUpdate()->firstOrFail();

            if ($bloqueada->estaAnulada()) {
                throw new DomainException('La factura ya está anulada.');
            }

            $bloqueada->update([
                'estado' => 'Anulada',
                'fecha_anulacion' => now(),
                'motivo_anulacion' => $motivo,
            ]);

            return $bloqueada->fresh('venta');
        });
    }

    /**
     * Numeración correlativa por gestión: F-2026-000001.
     */
    private function siguienteNumero(): string
    {
        $gestion = now()->format('Y');
        $secuencia = FacturaElectronica::whereYear('fecha_emision', $gestion)->count() + 1;

        return 'F-'.$gestion.'-'.str_pad((string) $secuencia, 6, '0', STR_PAD_LEFT);
    }

    /**
     * CUF simulado de 49 caracteres. El real lo calcula el SIN con la clave
     * del contribuyente y la fecha/hora exacta.
     */
    private function generarCuf(Venta $venta): string
    {
        $semilla = implode('|', [
            $venta->id,
            $venta->total,
            now()->format('YmdHis'),
            random_int(100000, 999999),
        ]);

        return strtoupper(substr(hash('sha256', $semilla), 0, 49));
    }

    /**
     * XML mínimo con los datos que exige la representación gráfica.
     */
    private function xmlSimulado(Venta $venta): string
    {
        $venta->loadMissing(['cliente', 'detalles.producto']);

        $lineas = $venta->detalles->map(fn ($detalle): string => sprintf(
            '<detalle><descripcion>%s</descripcion><cantidad>%d</cantidad><precio>%s</precio></detalle>',
            e($detalle->producto?->nombre ?? 'Producto'),
            (int) $detalle->cantidad,
            number_format((float) $detalle->precio_unitario, 2, '.', '')
        ))->implode('');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<factura><cabecera><venta>'.$venta->id.'</venta>'
            .'<cliente>'.e($venta->cliente?->nombre ?? 'Consumidor final').'</cliente>'
            .'<total>'.number_format((float) $venta->total, 2, '.', '').'</total>'
            .'<fecha>'.now()->toIso8601String().'</fecha>'
            .'<modo>simulacion</modo></cabecera>'
            .'<detalles>'.$lineas.'</detalles></factura>';
    }
}
