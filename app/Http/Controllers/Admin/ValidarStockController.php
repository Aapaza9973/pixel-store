<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ValidarStockRequest;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * 5.4.2: revalidación de stock justo antes de confirmar la venta.
 *
 * Es un chequeo de cortesía para que el POS avise antes de enviar la venta; la
 * garantía real sigue siendo la validación con lock dentro de VentaService.
 * El lock se toma dentro de una transacción para que se libere al responder.
 */
class ValidarStockController extends Controller
{
    public function __invoke(ValidarStockRequest $request): JsonResponse
    {
        $solicitadas = $this->cantidadesPorProducto($request->validated('items'));

        $errores = DB::transaction(function () use ($solicitadas): array {
            $errores = [];

            foreach ($solicitadas as $productoId => $cantidad) {
                $producto = Producto::whereKey($productoId)->lockForUpdate()->first();

                if ($producto === null) {
                    continue;
                }

                if ((int) $producto->stock < $cantidad) {
                    $errores[] = [
                        'producto_id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'stock_actual' => (int) $producto->stock,
                        'solicitado' => $cantidad,
                    ];
                }
            }

            return $errores;
        });

        if ($errores !== []) {
            return response()->json([
                'ok' => false,
                'errores' => $errores,
            ], 422);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Suma las cantidades por producto: dos líneas del mismo producto se
     * validan juntas, igual que en VentaService.
     *
     * @param  array<int, array{producto_id:int, cantidad:int}>  $items
     * @return array<int, int>
     */
    private function cantidadesPorProducto(array $items): array
    {
        $cantidades = [];

        foreach ($items as $item) {
            $productoId = (int) $item['producto_id'];
            $cantidades[$productoId] = ($cantidades[$productoId] ?? 0) + (int) $item['cantidad'];
        }

        return $cantidades;
    }
}
