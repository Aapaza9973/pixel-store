<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class InventarioController extends Controller
{
    /**
     * Panel de control del módulo de inventario.
     *
     * El acceso está restringido por el middleware `inventario.access`
     * (permiso `ver inventario`; Admin pasa por Gate::before).
     */
    public function index(InventoryService $inventario): View
    {
        return view('admin.inventario.index', $inventario->resumen());
    }

    /**
     * Métricas en JSON para el refresco en tiempo real del panel (HU-4.5).
     */
    public function kpis(InventoryService $inventario): JsonResponse
    {
        $resumen = $inventario->resumen();

        return response()->json([
            'productos_totales' => $resumen['productosTotales'],
            'stock_bajo' => $resumen['productosStockBajo'],
            'agotados' => $resumen['productosAgotados'],
            'valor_inventario' => $resumen['valorInventarioBs'],
            'actualizado_en' => now()->toIso8601String(),
        ]);
    }
}
