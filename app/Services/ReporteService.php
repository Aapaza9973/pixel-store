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

    /**
     * Ventas cobradas agrupadas por vendedor.
     *
     * @return list<array{vendedor_id:int, nombre:string, cantidad:int, total:float}>
     */
    public function ventasPorVendedor(string $desde, string $hasta): array
    {
        $filas = DB::table('ventas')
            ->join('users', 'users.id', '=', 'ventas.user_id')
            ->whereIn('ventas.estado', self::ESTADOS_COBRADOS)
            ->whereBetween('ventas.fecha', [$this->inicio($desde), $this->fin($hasta)])
            ->selectRaw('ventas.user_id AS vendedor_id, users.name AS nombre, COUNT(*) AS cantidad, SUM(ventas.total) AS total')
            ->groupBy('ventas.user_id', 'users.name')
            ->orderByDesc('total')
            ->get();

        return $filas->map(fn ($fila): array => [
            'vendedor_id' => (int) $fila->vendedor_id,
            'nombre' => $fila->nombre,
            'cantidad' => (int) $fila->cantidad,
            'total' => round((float) $fila->total, 2),
        ])->all();
    }

    /**
     * Ventas cobradas agrupadas por categoría de producto.
     *
     * @return list<array{categoria:string, cantidad:int, total:float}>
     */
    public function ventasPorCategoria(string $desde, string $hasta): array
    {
        $filas = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->join('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->whereIn('ventas.estado', self::ESTADOS_COBRADOS)
            ->whereBetween('ventas.fecha', [$this->inicio($desde), $this->fin($hasta)])
            ->selectRaw('categorias.nombre AS categoria, SUM(detalle_ventas.cantidad) AS cantidad, SUM(detalle_ventas.cantidad * detalle_ventas.precio_unitario) AS total')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('total')
            ->get();

        return $filas->map(fn ($fila): array => [
            'categoria' => $fila->categoria,
            'cantidad' => (int) $fila->cantidad,
            'total' => round((float) $fila->total, 2),
        ])->all();
    }

    /**
     * Métodos de pago más usados en ventas cobradas.
     *
     * @return list<array{metodo:string, cantidad:int, total:float}>
     */
    public function metodosPagoMasUsados(string $desde, string $hasta): array
    {
        $filas = DB::table('pagos')
            ->join('ventas', 'ventas.id', '=', 'pagos.venta_id')
            ->whereIn('ventas.estado', self::ESTADOS_COBRADOS)
            ->whereBetween('ventas.fecha', [$this->inicio($desde), $this->fin($hasta)])
            ->selectRaw('pagos.metodo AS metodo, COUNT(*) AS cantidad, SUM(pagos.monto) AS total')
            ->groupBy('pagos.metodo')
            ->orderByDesc('total')
            ->get();

        return $filas->map(fn ($fila): array => [
            'metodo' => $fila->metodo,
            'cantidad' => (int) $fila->cantidad,
            'total' => round((float) $fila->total, 2),
        ])->all();
    }

    /**
     * Utilidad neta por producto: ingresos menos costo histórico (HU-6.4.1-6.4.3).
     *
     * `costo` y `utilidad` son null cuando ninguna línea del producto tiene
     * `precio_costo` (ventas anteriores a la migración 6.4.4).
     *
     * @return list<array{producto_id:int, nombre:string, cantidad:int, ingresos:float, costo:float|null, utilidad:float|null}>
     */
    public function utilidadNetaPorProducto(string $desde, string $hasta): array
    {
        $filas = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->whereIn('ventas.estado', self::ESTADOS_COBRADOS)
            ->whereBetween('ventas.fecha', [$this->inicio($desde), $this->fin($hasta)])
            ->selectRaw(
                'productos.id AS producto_id, productos.nombre AS nombre,
                 SUM(detalle_ventas.cantidad) AS cantidad,
                 SUM(detalle_ventas.cantidad * detalle_ventas.precio_unitario) AS ingresos,
                 SUM(CASE WHEN detalle_ventas.precio_costo IS NOT NULL
                          THEN detalle_ventas.cantidad * detalle_ventas.precio_costo END) AS costo,
                 COUNT(detalle_ventas.precio_costo) AS lineas_con_costo'
            )
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('ingresos')
            ->get();

        return $filas->map(function ($fila): array {
            $ingresos = round((float) $fila->ingresos, 2);
            $costo = (int) $fila->lineas_con_costo > 0 ? round((float) $fila->costo, 2) : null;

            return [
                'producto_id' => (int) $fila->producto_id,
                'nombre' => $fila->nombre,
                'cantidad' => (int) $fila->cantidad,
                'ingresos' => $ingresos,
                'costo' => $costo,
                'utilidad' => $costo === null ? null : round($ingresos - $costo, 2),
            ];
        })->all();
    }

    /**
     * Ranking de productos por total vendido (HU-6.4.5).
     *
     * @param  'asc'|'desc'  $orden
     * @return list<array{producto_id:int, nombre:string, cantidad:int, total:float}>
     */
    public function rankingProductos(string $desde, string $hasta, int $limite = 10, string $orden = 'desc'): array
    {
        $sentido = $orden === 'asc' ? 'asc' : 'desc';

        $filas = DB::table('detalle_ventas')
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->whereIn('ventas.estado', self::ESTADOS_COBRADOS)
            ->whereBetween('ventas.fecha', [$this->inicio($desde), $this->fin($hasta)])
            ->selectRaw('productos.id AS producto_id, productos.nombre AS nombre, SUM(detalle_ventas.cantidad) AS cantidad, SUM(detalle_ventas.cantidad * detalle_ventas.precio_unitario) AS total')
            ->groupBy('productos.id', 'productos.nombre')
            ->orderBy('total', $sentido)
            ->limit($limite)
            ->get();

        return $filas->map(fn ($fila): array => [
            'producto_id' => (int) $fila->producto_id,
            'nombre' => $fila->nombre,
            'cantidad' => (int) $fila->cantidad,
            'total' => round((float) $fila->total, 2),
        ])->all();
    }

    /**
     * Antigüedad del inventario: productos con stock y días desde su última venta
     * cobrada (HU-6.4.6-6.4.7). `dias_sin_venta` es null si nunca se vendió.
     *
     * @return list<array{producto_id:int, nombre:string, stock:int, dias_sin_venta:int|null}>
     */
    public function antiguedadInventario(): array
    {
        $filas = DB::table('productos')
            ->where('productos.stock', '>', 0)
            ->select('productos.id AS producto_id', 'productos.nombre', 'productos.stock')
            ->selectRaw(
                '(SELECT MAX(ventas.fecha)
                    FROM detalle_ventas
                    INNER JOIN ventas ON ventas.id = detalle_ventas.venta_id
                   WHERE detalle_ventas.producto_id = productos.id
                     AND ventas.estado IN (?, ?, ?)) AS ultima_venta',
                self::ESTADOS_COBRADOS
            )
            ->get();

        $hoy = Carbon::now()->startOfDay();

        return $filas->map(fn ($fila): array => [
            'producto_id' => (int) $fila->producto_id,
            'nombre' => $fila->nombre,
            'stock' => (int) $fila->stock,
            'dias_sin_venta' => $fila->ultima_venta === null
                ? null
                : (int) Carbon::parse($fila->ultima_venta)->startOfDay()->diffInDays($hoy),
        ])
            // Nunca vendidos primero: son los más antiguos del inventario.
            ->sort(fn (array $a, array $b): int => ($b['dias_sin_venta'] ?? PHP_INT_MAX) <=> ($a['dias_sin_venta'] ?? PHP_INT_MAX))
            ->values()
            ->all();
    }

    private function inicio(string $desde): string
    {
        return Carbon::parse($desde)->startOfDay()->toDateTimeString();
    }

    private function fin(string $hasta): string
    {
        return Carbon::parse($hasta)->endOfDay()->toDateTimeString();
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
