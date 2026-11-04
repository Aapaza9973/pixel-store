<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportePeriodoRequest;
use App\Services\ReporteService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

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
}
