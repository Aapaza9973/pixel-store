<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Exceptions\SerieNoDisponibleException;
use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVentaRequest;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\DescuentoService;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Ramsey\Uuid\Uuid;

/**
 * Punto de venta (POS).
 *
 * Día 3.1: confirmación real sobre VentaService, con descuento validado en el
 * servidor y mapeo de excepciones de dominio a 409/422.
 */
class VentaController extends Controller
{
    public function create(): View
    {
        // Límite de descuento del rol del cajero para la UI del carrito (HU-5.10).
        // Se resuelve con el primer rol, igual que la validación del servidor,
        // para que la UI no ofrezca un límite que luego se rechace.
        $descuentoMaximo = (float) (auth()->user()?->roles->first()?->descuento_maximo ?? 0);

        return view('admin.ventas.create', compact('descuentoMaximo'));
    }

    /**
     * Confirma la venta del POS.
     *
     * El cliente envía un porcentaje de descuento; el monto y el total los
     * calcula el servidor con los precios de la base.
     */
    public function store(StoreVentaRequest $request, VentaService $ventas, DescuentoService $descuentos): JsonResponse
    {
        $datos = $request->validated();
        $items = $datos['items'];
        $pagos = $datos['pagos'];
        $porcentaje = (float) ($datos['descuento_porcentaje'] ?? 0);

        try {
            $subtotal = $this->subtotalDesdeBd($items);

            $this->validarDescuento($request, $descuentos, $porcentaje, $subtotal);

            $venta = $ventas->confirmar($items, $pagos, [
                'cliente_id' => $datos['meta']['cliente_id'] ?? null,
                'vendedor_id' => $datos['meta']['vendedor_id'] ?? $request->user()->id,
                'operacion_id' => $this->normalizarOperacionId($datos['meta']['operacion_id'] ?? null),
                'descuento_porcentaje' => $porcentaje,
                'autorizado_por' => $datos['autorizado_por'] ?? null,
            ]);
        } catch (StockInsuficienteException|SerieNoDisponibleException $e) {
            return $this->respuestaDominio($e, 409);
        } catch (DomainException $e) {
            // PagoInsuficiente y cualquier otra regla de negocio: 422, nunca 500.
            return $this->respuestaDominio($e, 422);
        }

        return response()->json([
            'message' => "Venta #{$venta->id} confirmada.",
            'venta' => [
                'id' => $venta->id,
                'operacion_id' => $venta->operacion_id,
                'subtotal' => round((float) $venta->total + (float) $venta->descuento, 2),
                'descuento' => (float) $venta->descuento,
                'descuento_porcentaje' => (float) $venta->descuento_porcentaje,
                'total' => (float) $venta->total,
                'estado' => $venta->estado,
            ],
        ], 201);
    }

    /**
     * Series disponibles de un producto para el selector del POS (HU-5.6).
     */
    public function series(Producto $producto): JsonResponse
    {
        $this->authorize('viewAny', Producto::class);

        $series = $producto->numerosSerie()
            ->disponibles()
            ->orderBy('numero_serie')
            ->get(['id', 'numero_serie']);

        return response()->json([
            'data' => $series->map(fn ($serie) => [
                'id' => $serie->id,
                'numero_serie' => $serie->numero_serie,
            ]),
        ]);
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
                'maneja_numero_serie' => (bool) $producto->maneja_numero_serie,
                'categoria' => $producto->categoria?->nombre,
                'marca' => $producto->marca?->nombre,
            ]),
        ]);
    }

    /**
     * Subtotal con los precios vigentes en la base (no los del cliente).
     *
     * @param  array<int, array{producto_id:int, cantidad:int}>  $items
     */
    private function subtotalDesdeBd(array $items): float
    {
        $precios = Producto::whereIn('id', collect($items)->pluck('producto_id')->unique()->all())
            ->pluck('precio_unitario', 'id');

        $subtotal = 0.0;

        foreach ($items as $item) {
            $subtotal += (float) ($precios[$item['producto_id']] ?? 0) * (int) $item['cantidad'];
        }

        return round($subtotal, 2);
    }

    /**
     * El límite de descuento aplica al rol AUTENTICADO, no a meta.vendedor_id.
     *
     * @throws DomainException
     */
    private function validarDescuento(
        StoreVentaRequest $request,
        DescuentoService $descuentos,
        float $porcentaje,
        float $subtotal
    ): void {
        if ($porcentaje <= 0) {
            return;
        }

        $rol = $request->user()->roles->first()?->name;
        $montoDescuento = round($subtotal * $porcentaje / 100, 2);

        if ($rol === null || ! $descuentos->validar($rol, $montoDescuento, $subtotal)) {
            throw new DomainException(sprintf(
                'El descuento de %s %% supera el máximo permitido para tu rol. '
                .'Requiere autorización de administrador.',
                rtrim(rtrim(number_format($porcentaje, 2, '.', ''), '0'), '.')
            ));
        }
    }

    /**
     * Normaliza la clave de idempotencia a UUID: la columna es UUID UNIQUE.
     *
     * Un token legible del cliente ('pos-abc') se mapea de forma determinista
     * a UUID v5, de modo que reintentar con el mismo token siga siendo
     * idempotente sin fallar por formato.
     */
    private function normalizarOperacionId(?string $token): string
    {
        $token = trim((string) $token);

        if ($token === '') {
            return (string) Str::uuid();
        }

        return Str::isUuid($token)
            ? strtolower($token)
            : (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'pixelstore:venta:'.$token);
    }

    /**
     * @param  int  $status  409 para stock/serie, 422 para reglas de negocio
     */
    private function respuestaDominio(DomainException $e, int $status): JsonResponse
    {
        return response()->json([
            'message' => $e->getUserMessage(),
            'code' => $e->getErrorCode(),
        ], $status);
    }
}
