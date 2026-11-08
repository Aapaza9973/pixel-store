<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ReporteExport;
use App\Models\CierreCaja;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportePeriodoRequest;
use App\Services\ReporteService;
use App\Services\CierreCajaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reportería del negocio (HU-6.2).
 */
class ReporteController extends Controller
{
    /**
     * Totales por período (día/semana/mes/año) entre dos fechas.
     */
    public function periodo(ReportePeriodoRequest $request, ReporteService $reportes): View
    {
        $datos = $request->validated();

        $desde = $datos['desde'] ?? Carbon::now()->startOfMonth()->toDateString();
        $hasta = $datos['hasta'] ?? Carbon::now()->toDateString();
        $agrupacion = $datos['agrupacion'] ?? 'dia';

        $filas = $reportes->porPeriodo($desde, $hasta, $agrupacion);

        $totalVentas = round((float) collect($filas)->sum('total_ventas'), 2);
        $cantidadVentas = (int) collect($filas)->sum('cantidad_ventas');

        return view('admin.reportes.periodo', compact(
            'desde', 'hasta', 'agrupacion', 'filas', 'totalVentas', 'cantidadVentas'
        ));
    }

    /**
     * Exporta el reporte por período a Excel (.xlsx) — HU-6.5.
     */
    public function exportarExcel(ReportePeriodoRequest $request, ReporteService $reportes, CierreCajaService $caja): BinaryFileResponse
    {
        $datos = $request->validated();
        $tipo = (string) $request->input('tipo', 'periodo');

        abort_unless(in_array($tipo, ['diario', 'periodo', 'vendedor', 'categoria', 'metodos', 'indicadores'], true), 422);

        $desde = $datos['desde'] ?? Carbon::now()->startOfMonth()->toDateString();
        $hasta = $datos['hasta'] ?? Carbon::now()->toDateString();
        $agrupacion = $datos['agrupacion'] ?? 'dia';

        $resumen = [];
        $formatos = [];

        if ($tipo === 'diario') {
            $fecha = $request->validate(['fecha' => ['required', 'date_format:Y-m-d']])['fecha'];
            [$titulo, $encabezados, $filas, $resumen, $formatos] = $this->datosExcelDiario($fecha, $caja);
            $desde = $fecha;
            $hasta = $fecha;
            $subtitulo = 'Cierre diario de ventas, métodos de pago y arqueo de caja · '.Carbon::parse($fecha)->format('d/m/Y');
        } elseif ($tipo === 'periodo') {
            $datosPeriodo = $reportes->porPeriodo($desde, $hasta, $agrupacion);
            $filas = array_map(fn (array $fila): array => [
                $fila['periodo'],
                (int) $fila['cantidad_ventas'],
                (float) $fila['total_ventas'],
            ], $datosPeriodo);
            $titulo = 'Reporte de ventas por período';
            $encabezados = ['Período', 'Cantidad de ventas', 'Total vendido (Bs)'];
            $resumen = [
                ['Rango consultado', Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y')],
                ['Agrupación', ucfirst($agrupacion)],
                ['Ventas registradas', (int) collect($datosPeriodo)->sum('cantidad_ventas')],
            ];
            $formatos = ['C' => '"Bs" #,##0.00;[Red]-"Bs" #,##0.00'];
            $subtitulo = 'Análisis de ventas agrupado por '.strtolower($agrupacion).' · '.Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y');
        } else {
            [$titulo, $encabezados, $filas] = $this->datosPdf($tipo, $desde, $hasta, $agrupacion, $reportes);
            $subtitulo = 'Período consultado: '.Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y');
        }

        $export = new ReporteExport($filas, $encabezados, $titulo, $subtitulo, $resumen, $formatos);

        return Excel::download($export, "reporte-{$tipo}-{$desde}-a-{$hasta}.xlsx");
    }

    /**
     * Reporte específico: ventas por vendedor (HU-6.3).
     */
    public function vendedor(Request $request, ReporteService $reportes): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('admin.reportes.especificos', [
            'tipo' => 'vendedor',
            'desde' => $desde,
            'hasta' => $hasta,
            'filas' => $reportes->ventasPorVendedor($desde, $hasta),
        ]);
    }

    /**
     * Reporte específico: ventas por categoría (HU-6.3).
     */
    public function categoria(Request $request, ReporteService $reportes): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('admin.reportes.especificos', [
            'tipo' => 'categoria',
            'desde' => $desde,
            'hasta' => $hasta,
            'filas' => $reportes->ventasPorCategoria($desde, $hasta),
        ]);
    }

    /**
     * Reporte específico: métodos de pago más usados (HU-6.3).
     */
    public function metodos(Request $request, ReporteService $reportes): View
    {
        [$desde, $hasta] = $this->rango($request);

        return view('admin.reportes.especificos', [
            'tipo' => 'metodos',
            'desde' => $desde,
            'hasta' => $hasta,
            'filas' => $reportes->metodosPagoMasUsados($desde, $hasta),
        ]);
    }

    /**
     * Indicadores del negocio: utilidad, ranking y antigüedad de inventario
     * (HU-6.4.1-6.4.7).
     */
    public function indicadores(Request $request, ReporteService $reportes): View
    {
        [$desde, $hasta] = $this->rango($request);

        $utilidad = $reportes->utilidadNetaPorProducto($desde, $hasta);

        $ingresos = round((float) collect($utilidad)->sum('ingresos'), 2);
        $hayCosto = collect($utilidad)->contains(fn (array $fila): bool => $fila['costo'] !== null);
        $costo = $hayCosto
            ? round((float) collect($utilidad)->sum(fn (array $fila): float => (float) ($fila['costo'] ?? 0)), 2)
            : null;
        $utilidadTotal = $costo === null ? null : round($ingresos - $costo, 2);
        $margen = ($utilidadTotal === null || $ingresos <= 0)
            ? null
            : round(($utilidadTotal / $ingresos) * 100, 1);

        return view('admin.reportes.indicadores', [
            'desde' => $desde,
            'hasta' => $hasta,
            'utilidad' => $utilidad,
            'hayCosto' => $hayCosto,
            'ingresos' => $ingresos,
            'costo' => $costo,
            'utilidadTotal' => $utilidadTotal,
            'margen' => $margen,
            'ranking' => $reportes->rankingProductos($desde, $hasta, 10, 'desc'),
            'menosVendidos' => $reportes->rankingProductos($desde, $hasta, 10, 'asc'),
            'antiguedad' => $reportes->antiguedadInventario(),
        ]);
    }

    /**
     * Exporta a PDF el reporte indicado en `?tipo=` (HU-6.5.1-6.5.3).
     *
     * Un solo endpoint parametrizado: periodo, vendedor, categoria, metodos o
     * indicadores, siempre con el rango de fechas actual.
     */
    public function pdf(Request $request, ReporteService $reportes, CierreCajaService $caja): Response
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(['diario', 'periodo', 'vendedor', 'categoria', 'metodos', 'indicadores'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'agrupacion' => ['nullable', Rule::in(array_keys(ReporteService::AGRUPACIONES))],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $tipo = $datos['tipo'];
        $desde = $datos['desde'] ?? Carbon::now()->startOfMonth()->toDateString();
        $hasta = $datos['hasta'] ?? Carbon::now()->toDateString();
        $agrupacion = $datos['agrupacion'] ?? 'dia';

        if ($tipo === 'diario') {
            $fecha = $datos['fecha'] ?? Carbon::now()->toDateString();
            [$titulo, $columnas, $filas] = $this->datosDiario($fecha, $caja);
            $desde = $fecha;
            $hasta = $fecha;
        } else {
            [$titulo, $columnas, $filas] = $this->datosPdf($tipo, $desde, $hasta, $agrupacion, $reportes);
        }

        $pdf = Pdf::loadView('admin.reportes.pdf', compact('titulo', 'columnas', 'filas', 'desde', 'hasta'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("reporte-{$tipo}-{$desde}-{$hasta}.pdf");
    }

    /**
     * Datos estructurados y numéricos para el Excel de caja (HU-6.1 / HU-6.5).
     * Se reutiliza el mismo servicio de cálculo que alimenta la pantalla de caja.
     */
    private function datosExcelDiario(string $fecha, CierreCajaService $caja): array
    {
        $resumenCaja = $caja->calcularDelDia($fecha);
        $filas = [];

        foreach ($resumenCaja['por_metodo'] as $metodo => $monto) {
            $filas[] = ['VENTAS', 'Cobros recibidos', $metodo, (float) $monto, null, null, null];
        }

        $filas[] = ['AJUSTES', 'Devoluciones del día', 'Devoluciones', null, (float) $resumenCaja['total_devoluciones'], null, null];
        $filas[] = ['CAJA', 'Efectivo esperado', 'Sistema', null, (float) $resumenCaja['efectivo_esperado'], null, null];

        $cierres = CierreCaja::query()
            ->with('user:id,name')
            ->whereDate('fecha', $fecha)
            ->orderBy('turno')
            ->get();

        foreach ($cierres as $cierre) {
            $filas[] = [
                'CIERRE DE CAJA',
                'Arqueo de turno',
                ucfirst($cierre->turno).' · '.($cierre->user?->name ?? 'Sin usuario'),
                null,
                (float) $cierre->efectivo_esperado,
                (float) $cierre->efectivo_real,
                (float) $cierre->diferencia,
            ];
        }

        return [
            'Reporte diario de ventas y cierre de caja',
            ['Sección', 'Concepto', 'Método / turno', 'Ventas (Bs)', 'Esperado (Bs)', 'Real (Bs)', 'Diferencia (Bs)'],
            $filas,
            [
                ['Fecha', Carbon::parse($fecha)->format('d/m/Y')],
                ['Total cobrado (Bs)', (float) $resumenCaja['total_ventas']],
                ['Efectivo esperado (Bs)', (float) $resumenCaja['efectivo_esperado']],
            ],
            [
                'D' => '"Bs" #,##0.00;[Red]-"Bs" #,##0.00',
                'E' => '"Bs" #,##0.00;[Red]-"Bs" #,##0.00',
                'F' => '"Bs" #,##0.00;[Red]-"Bs" #,##0.00',
                'G' => '"Bs" #,##0.00;[Red]-"Bs" #,##0.00',
            ],
        ];
    }

    /**
     * Datos exportables del reporte diario de caja, reutilizando el cálculo
     * oficial del cierre para que los importes coincidan con la pantalla.
     */
    private function datosDiario(string $fecha, CierreCajaService $caja): array
    {
        $resumen = $caja->calcularDelDia($fecha);
        $filas = [];

        foreach ($resumen['por_metodo'] as $metodo => $monto) {
            $filas[] = ['Ventas por método', $metodo, 'Bs '.number_format($monto, 2, ',', '.'), '—'];
        }

        $filas[] = ['Devoluciones', 'Total', 'Bs '.number_format($resumen['total_devoluciones'], 2, ',', '.'), '—'];
        $filas[] = ['Efectivo esperado', 'Caja', 'Bs '.number_format($resumen['efectivo_esperado'], 2, ',', '.'), '—'];

        $cierres = CierreCaja::query()->with('user:id,name')->whereDate('fecha', $fecha)->orderBy('turno')->get();
        foreach ($cierres as $cierre) {
            $filas[] = [
                'Cierre de caja',
                ucfirst($cierre->turno).' · '.($cierre->user?->name ?? '—'),
                'Esperado Bs '.number_format((float) $cierre->efectivo_esperado, 2, ',', '.'),
                'Real Bs '.number_format((float) $cierre->efectivo_real, 2, ',', '.').' · Diferencia Bs '.number_format((float) $cierre->diferencia, 2, ',', '.'),
            ];
        }

        return ['Reporte diario de ventas y cierre de caja', ['Concepto', 'Detalle', 'Importe', 'Información adicional'], $filas];
    }

    /**
     * Arma título, columnas y filas del PDF según el tipo de reporte.
     *
     * @return array{0: string, 1: list<string>, 2: list<list<string>>}
     */
    private function datosPdf(string $tipo, string $desde, string $hasta, string $agrupacion, ReporteService $reportes): array
    {
        $dinero = fn (float $valor): string => 'Bs '.number_format($valor, 2, ',', '.');

        return match ($tipo) {
            'periodo' => [
                'Reporte por período · '.ucfirst($agrupacion),
                ['Período', 'Cantidad', 'Total'],
                array_map(fn (array $fila): array => [
                    $fila['periodo'],
                    (string) $fila['cantidad_ventas'],
                    $dinero((float) $fila['total_ventas']),
                ], $reportes->porPeriodo($desde, $hasta, $agrupacion)),
            ],
            'vendedor' => [
                'Ventas por vendedor',
                ['Vendedor', 'Cantidad', 'Total'],
                array_map(fn (array $fila): array => [
                    $fila['nombre'],
                    (string) $fila['cantidad'],
                    $dinero((float) $fila['total']),
                ], $reportes->ventasPorVendedor($desde, $hasta)),
            ],
            'categoria' => [
                'Ventas por categoría',
                ['Categoría', 'Cantidad', 'Total'],
                array_map(fn (array $fila): array => [
                    $fila['categoria'],
                    (string) $fila['cantidad'],
                    $dinero((float) $fila['total']),
                ], $reportes->ventasPorCategoria($desde, $hasta)),
            ],
            'metodos' => [
                'Métodos de pago más usados',
                ['Método', 'Cantidad', 'Total'],
                array_map(fn (array $fila): array => [
                    $fila['metodo'],
                    (string) $fila['cantidad'],
                    $dinero((float) $fila['total']),
                ], $reportes->metodosPagoMasUsados($desde, $hasta)),
            ],
            default => [
                'Indicadores de la tienda',
                ['Indicador', 'Producto', 'Cantidad / stock', 'Ingreso / total / días', 'Costo / utilidad'],
                array_merge(
                    array_map(fn (array $fila): array => [
                        'Utilidad por producto', $fila['nombre'], (string) $fila['cantidad'],
                        $dinero((float) $fila['ingresos']),
                        $fila['costo'] === null || $fila['utilidad'] === null
                            ? 'Costo no registrado'
                            : $dinero((float) $fila['costo']).' / '.$dinero((float) $fila['utilidad']),
                    ], $reportes->utilidadNetaPorProducto($desde, $hasta)),
                    array_map(fn (array $fila): array => [
                        'Más vendidos', $fila['nombre'], (string) $fila['cantidad'],
                        $dinero((float) $fila['total']), '—',
                    ], $reportes->rankingProductos($desde, $hasta, 10, 'desc')),
                    array_map(fn (array $fila): array => [
                        'Menos vendidos', $fila['nombre'], (string) $fila['cantidad'],
                        $dinero((float) $fila['total']), '—',
                    ], $reportes->rankingProductos($desde, $hasta, 10, 'asc')),
                    array_map(fn (array $fila): array => [
                        'Antigüedad inventario', $fila['nombre'], (string) $fila['stock'],
                        $fila['dias_desde_entrada'] === null ? 'Sin entrada registrada' : (string) $fila['dias_desde_entrada'].' días',
                        '—',
                    ], $reportes->antiguedadInventario())
                ),
            ],
        };
    }

    /**
     * @return array{0: string, 1: string} [desde, hasta]
     */
    private function rango(Request $request): array
    {
        $desde = $request->string('desde')->toString() ?: Carbon::now()->startOfMonth()->toDateString();
        $hasta = $request->string('hasta')->toString() ?: Carbon::now()->toDateString();

        return [$desde, $hasta];
    }
}
