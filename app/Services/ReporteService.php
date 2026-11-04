<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reportes agregados del negocio (HU-6.2).
 *
 * Comparte el criterio de cobro de CierreCajaService: una venta cuenta si su
 * estado es 'Pagado', 'parcialmente_devuelta' o 'totalmente_devuelta'. En los
 * tres casos el dinero entró a caja; `Pendiente` y `Cancelada` quedan fuera.
 * (Venta::estaPagada() NO sirve acá: solo devuelve true para 'Pagado'.)
 */
class ReporteService
{
    /** Estados de venta en los que el dinero ya fue cobrado. */
    public const ESTADOS_COBRADOS = ['Pagado', 'parcialmente_devuelta', 'totalmente_devuelta'];

    /** Agrupación aceptada => unidad de DATE_TRUNC. */
    public const AGRUPACIONES = [
        'dia' => 'day',
        'semana' => 'week',
        'mes' => 'month',
        'anio' => 'year',
    ];

    /**
     * Totales de ventas cobradas agrupados por período.
     *
     * `periodo` se formatea según la agrupación: dia/semana 'Y-m-d' (la semana
     * empieza el lunes), mes 'Y-m', anio 'Y'.
     *
     * @return list<array{periodo: string, total_ventas: float, cantidad_ventas: int}>
     *
     * @throws DomainException si la agrupación no es válida
     */
    public function porPeriodo(string $desde, string $hasta, string $agrupacion = 'dia'): array
    {
        if (! isset(self::AGRUPACIONES[$agrupacion])) {
            throw new DomainException(
                "La agrupación «{$agrupacion}» no es válida. Use: dia, semana, mes o anio."
            );
        }

        // La unidad sale de la whitelist de AGRUPACIONES, nunca del usuario.
        // No se usa binding: Postgres trataría $1 en el SELECT y $2 en el
        // GROUP BY como expresiones distintas y rechazaría la agrupación.
        $expresion = "DATE_TRUNC('".self::AGRUPACIONES[$agrupacion]."', ventas.fecha)";

        $desdeDia = Carbon::parse($desde)->toDateString();
        $hastaDia = Carbon::parse($hasta)->toDateString();

        $filas = DB::table('ventas')
            ->whereIn('ventas.estado', self::ESTADOS_COBRADOS)
            ->whereBetween('ventas.fecha', [$desdeDia.' 00:00:00', $hastaDia.' 23:59:59'])
            ->selectRaw(
                $expresion.' AS periodo,
                 SUM(ventas.total) AS total_ventas,
                 COUNT(*) AS cantidad_ventas'
            )
            ->groupByRaw($expresion)
            ->orderBy('periodo')
            ->get();

        return $filas->map(fn ($fila): array => [
            'periodo' => $this->formatearPeriodo(Carbon::parse($fila->periodo), $agrupacion),
            'total_ventas' => round((float) $fila->total_ventas, 2),
            'cantidad_ventas' => (int) $fila->cantidad_ventas,
        ])->all();
    }

    private function formatearPeriodo(Carbon $periodo, string $agrupacion): string
    {
        return match ($agrupacion) {
            'mes' => $periodo->format('Y-m'),
            'anio' => $periodo->format('Y'),
            default => $periodo->format('Y-m-d'),
        };
    }
}
