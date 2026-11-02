<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Punto de venta (POS).
 *
 * Día 2: búsqueda de productos para el carrito. La confirmación (POST) llega
 * en el Día 3 sobre VentaService.
 */
class VentaController extends Controller
{
    public function create(): View
    {
        // Límite de descuento del rol del cajero para la UI del carrito (HU-5.10).
        $descuentoMaximo = (float) (auth()->user()?->roles->max('descuento_maximo') ?? 0);

        return view('admin.ventas.create', compact('descuentoMaximo'));
    }

    /**
     * Búsqueda de productos por nombre, SKU o código de barras (HU-5.3).
     */
    public function buscarProductos(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Producto::class);

        $termino = trim($request->string('q')->toString());

        $productos = Producto::query()
            ->with(['categoria:id,nombre', 'marca:id,nombre'])
            ->when($termino !== '', fn ($query) => $query->buscar($termino))
            ->orderBy('nombre')
            ->limit(10)
            ->get(['id', 'nombre', 'sku', 'precio_unitario', 'stock', 'categoria_id', 'marca_id']);

        return response()->json([
            'data' => $productos->map(fn (Producto $producto) => [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'sku' => $producto->sku,
                'precio_unitario' => (float) $producto->precio_unitario,
                'stock' => (int) $producto->stock,
                'categoria' => $producto->categoria?->nombre,
                'marca' => $producto->marca?->nombre,
            ]),
        ]);
    }
}
