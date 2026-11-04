<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Exceptions\SerieNoDisponibleException;
use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AutorizarDescuentoRequest;
use App\Http\Requests\Admin\StoreVentaRequest;
use App\Models\AutorizacionDescuento;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    public function store(StoreVentaRequest $request, VentaService $ventas): JsonResponse
    {
        $datos = $request->validated();
        $items = $datos['items'];
        $pagos = $datos['pagos'];
        $porcentaje = (float) ($datos['descuento_porcentaje'] ?? 0);

        try {
            $venta = $ventas->confirmar($items, $pagos, [
                'cliente_id' => $datos['meta']['cliente_id'] ?? null,
                'vendedor_id' => $datos['meta']['vendedor_id'] ?? $request->user()->id,
                'operacion_id' => $this->normalizarOperacionId($datos['meta']['operacion_id'] ?? null),
                'descuento_porcentaje' => $porcentaje,
                'autorizado_por' => $datos['autorizado_por'] ?? null,
                // El límite aplica al rol AUTENTICADO, no a meta.vendedor_id.
                'rol_descuento' => $request->user()->roles->first()?->name,
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
     * Autoriza un descuento fuera del límite del rol (HU-5.11).
     *
     * El administrador confirma su identidad con email + contraseña, pero NO se
     * inicia sesión como él: `Auth::validate()` solo verifica las credenciales,
     * no toca el guard ni la sesión del vendedor que está haciendo la venta.
     */
    public function autorizarDescuento(AutorizarDescuentoRequest $request): JsonResponse
    {
        $datos = $request->validated();

        if (! Auth::validate([
            'email' => $datos['email'],
            'password' => $datos['password'],
        ])) {
            return $this->respuestaDominio(new DomainException('Credenciales incorrectas.'), 422);
        }

        $admin = User::where('email', $datos['email'])->firstOrFail();

        if (! $admin->hasRole('Admin')) {
            return response()->json([
                'message' => 'El usuario no tiene permisos para autorizar descuentos.',
                'code' => 'sin_permiso_autorizar',
            ], 403);
        }

        // Auth::validate() solo valida email+password: no mira `activo` (eso lo
        // hace el middleware user.active, y solo para el usuario autenticado).
        // Se chequea acá, después del rol, para que el mensaje sea informativo.
        if (! $admin->activo) {
            return response()->json([
                'message' => 'El administrador está desactivado.',
                'code' => 'admin_inactivo',
            ], 403);
        }

        $autorizacion = AutorizacionDescuento::create([
            'operacion_id' => $datos['operacion_id'],
            // La venta todavía no existe: se enlaza al confirmar (5.11.5).
            'venta_id' => null,
            'admin_id' => $admin->id,
            'vendedor_id' => $request->user()->id,
            'monto_descuento' => $datos['monto_descuento'],
            'descuento_porcentaje' => $datos['descuento_porcentaje'],
            'motivo' => $datos['motivo'] ?? null,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'autorizacion_id' => $autorizacion->id,
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
            ],
            'operacion_id' => $autorizacion->operacion_id,
        ], 201);
    }

    /**
     * Comprobante imprimible de una venta (Día 3.3).
     *
     * Sin numeración correlativa todavía: el número visible se deriva del PK
     * (#000123) para que el papel sea estable y reproducible.
     */
    public function comprobante(Venta $venta): View
    {
        abort_unless(auth()->user()?->can('ver ventas'), 403);

        $venta->load([
            'cliente',
            'vendedor',
            'autorizador',
            'pagos',
            'detalles.producto',
            'detalles.numeroSerie',
        ]);

        $numero = '#'.str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT);

        return view('admin.ventas.comprobante', compact('venta', 'numero'));
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
            ->get(['id', 'nombre', 'sku', 'precio_unitario', 'stock', 'maneja_numero_serie', 'categoria_id', 'marca_id']);

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
