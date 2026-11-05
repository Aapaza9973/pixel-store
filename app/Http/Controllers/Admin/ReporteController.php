<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ReporteExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportePeriodoRequest;
use App\Services\ReporteService;
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
    public function exportarExcel(ReportePeriodoRequest $request, ReporteService $reportes): BinaryFileResponse
    {
        $datos = $request->validated();

        $desde = $datos['desde'] ?? Carbon::now()->startOfMonth()->toDateString();
        $hasta = $datos['hasta'] ?? Carbon::now()->toDateString();
        $agrupacion = $datos['agrupacion'] ?? 'dia';

        $filas = $reportes->porPeriodo($desde, $hasta, $agrupacion);

        $export = new ReporteExport(
            array_map(
                fn (array $fila): array => [
                    $fila['periodo'],
                    $fila['cantidad_ventas'],
                    $fila['total_ventas'],
                ],
                $filas
            ),
            ['Periodo', 'Cantidad de ventas', 'Total ventas (Bs)'],
        );

        return Excel::download($export, "reporte-periodo-{$desde}-a-{$hasta}.xlsx");
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
            'ranking' => $reportes->rankingProductos($desde, $hasta),
            'antiguedad' => $reportes->antiguedadInventario(),
        ]);
    }

    /**
     * Exporta a PDF el reporte indicado en `?tipo=` (HU-6.5.1-6.5.3).
     *
     * Un solo endpoint parametrizado: periodo, vendedor, categoria, metodos o
     * indicadores, siempre con el rango de fechas actual.
     */
    public function pdf(Request $request, ReporteService $reportes): Response
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(['periodo', 'vendedor', 'categoria', 'metodos', 'indicadores'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'agrupacion' => ['nullable', Rule::in(array_keys(ReporteService::AGRUPACIONES))],
        ]);

        $tipo = $datos['tipo'];
        $desde = $datos['desde'] ?? Carbon::now()->startOfMonth()->toDateString();
        $hasta = $datos['hasta'] ?? Carbon::now()->toDateString();
        $agrupacion = $datos['agrupacion'] ?? 'dia';

        [$titulo, $columnas, $filas] = $this->datosPdf($tipo, $desde, $hasta, $agrupacion, $reportes);

        $pdf = Pdf::loadView('admin.reportes.pdf', compact('titulo', 'columnas', 'filas', 'desde', 'hasta'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("reporte-{$tipo}-{$desde}-{$hasta}.pdf");
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
                'Indicadores · utilidad neta por producto',
                ['Producto', 'Cantidad', 'Ingresos', 'Costo', 'Utilidad'],
                array_map(fn (array $fila): array => [
                    $fila['nombre'],
                    (string) $fila['cantidad'],
                    $dinero((float) $fila['ingresos']),
                    $fila['costo'] === null ? '—' : $dinero((float) $fila['costo']),
                    $fila['utilidad'] === null ? '—' : $dinero((float) $fila['utilidad']),
                ], $reportes->utilidadNetaPorProducto($desde, $hasta)),
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
