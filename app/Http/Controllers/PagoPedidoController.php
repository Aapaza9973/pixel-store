<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Models\Pago;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\View\View;

/**
 * Pago en línea del pedido web (HU-3.4).
 *
 * **Modo simulación**: aprueba el pedido y registra el pago sin cobrar de
 * verdad. Con `pagos.modo = produccion` las rutas responden 404 hasta que
 * exista la integración con Stripe/PayPal y sus claves.
 */
class PagoPedidoController extends Controller
{
    public function show(Venta $venta, string $token): View
    {
        $this->validar($venta, $token);

        return view('catalogo.pago', [
            'venta' => $venta->load(['detalles.producto', 'cliente']),
            'token' => $token,
        ]);
    }

    public function store(Venta $venta, string $token, VentaService $ventas): View
    {
        $this->validar($venta, $token);

        try {
            $cobrado = $ventas->pagarPedidoWeb($venta, $venta->user_id);
        } catch (DomainException $e) {
            return view('catalogo.pedido-estado', [
                'venta' => $venta->fresh(['detalles.producto', 'cliente']),
                'accion' => 'sin_cambios',
                'mensaje' => $e->getUserMessage(),
            ]);
        }

        // El cobro simulado deja el pago registrado para que el cierre de caja
        // lo cuente igual que un cobro de mostrador.
        Pago::firstOrCreate(
            ['venta_id' => $cobrado->id],
            [
                'monto' => $cobrado->total,
                'metodo' => 'Tarjeta',
                'estado' => 'Completado',
                'referencia' => 'SIMULADO-'.$cobrado->id,
            ]
        );

        return view('catalogo.pedido-estado', [
            'venta' => $cobrado->fresh(['detalles.producto', 'cliente']),
            'accion' => 'confirmado',
            'mensaje' => 'Pago aprobado en modo simulación. Te contactamos para coordinar la entrega.',
        ]);
    }

    private function validar(Venta $venta, string $token): void
    {
        abort_unless(config('pagos.modo') === 'simulado', 404);

        abort_unless($venta->origen === 'web', 404);
        abort_unless(
            is_string($venta->token_confirmacion)
                && $venta->token_confirmacion !== ''
                && hash_equals($venta->token_confirmacion, $token),
            404
        );
    }
}
