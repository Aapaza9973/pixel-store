<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ReporteExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportePeriodoRequest;
use App\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
     * @return array{0: string, 1: string} [desde, hasta]
     */
    private function rango(Request $request): array
    {
        $desde = $request->string('desde')->toString() ?: Carbon::now()->startOfMonth()->toDateString();
        $hasta = $request->string('hasta')->toString() ?: Carbon::now()->toDateString();

        return [$desde, $hasta];
    }
}
