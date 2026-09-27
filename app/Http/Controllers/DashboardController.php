<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\StockUbicacion;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Métricas base del inventario.
        $productosTotales = Producto::count();
        $productosStockBajo = Producto::stockBajo()->count();
        $productosAgotados = Producto::where('stock', 0)->count();
        $categoriasTotales = Categoria::count();
        $marcasTotales = Marca::count();
        $valorInventarioBs = (float) (Producto::selectRaw('SUM(precio_unitario * stock) as total')->value('total') ?? 0);

        // Top 8 categorías con más stock total (para el gráfico).
        $stockPorCategoria = Producto::query()
            ->join('categorias', 'productos.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.nombre as categoria_nombre, SUM(productos.stock) as total_stock')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderByDesc('total_stock')
            ->limit(8)
            ->get();

        // Productos críticos (stock ≤ umbral), los más urgentes primero.
        $productosCriticos = Producto::query()
            ->stockBajo()
            ->with(['categoria', 'marca'])
            ->orderByRaw('stock - umbral_alerta asc')
            ->limit(10)
            ->get();

        // Stock total por ubicación física.
        $stockPorUbicacion = StockUbicacion::query()
            ->with('ubicacion')
            ->groupBy('ubicacion_id')
            ->selectRaw('ubicacion_id, SUM(cantidad) as total_stock')
            ->get();

        return view('dashboard', compact(
            'productosTotales',
            'productosStockBajo',
            'productosAgotados',
            'categoriasTotales',
            'marcasTotales',
            'valorInventarioBs',
            'stockPorCategoria',
            'productosCriticos',
            'stockPorUbicacion'
        ));
    }
}
