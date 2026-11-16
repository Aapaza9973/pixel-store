<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\View\View;

/**
 * Enlaces del correo de pedido (HU-3.3). El token de 48 caracteres es la única
 * credencial: no hay sesión ni id enumerable.
 */
class PedidoPublicoController extends Controller
{
    public function confirmar(Venta $venta, string $token): View
    {
        $this->validarToken($venta, $token);

        if ($venta->estado === 'Pendiente' && $venta->confirmado_en === null) {
            $venta->update(['confirmado_en' => now()]);
        }

        return view('catalogo.pedido-estado', [
            'venta' => $venta->fresh(['detalles.producto', 'cliente']),
            'accion' => 'confirmado',
            'mensaje' => 'Confirmaste tu pedido. Te contactamos para coordinar la entrega.',
        ]);
    }

    public function cancelar(Venta $venta, string $token, VentaService $ventas): View
    {
        $this->validarToken($venta, $token);

        try {
            $cancelado = $ventas->cancelarPedidoWeb($venta);
        } catch (DomainException $e) {
            return view('catalogo.pedido-estado', [
                'venta' => $venta->fresh(['detalles.producto', 'cliente']),
                'accion' => 'sin_cambios',
                'mensaje' => $e->getUserMessage(),
            ]);
        }

        return view('catalogo.pedido-estado', [
            'venta' => $cancelado,
            'accion' => 'cancelado',
            'mensaje' => 'Cancelamos tu pedido y liberamos el stock reservado.',
        ]);
    }

    private function validarToken(Venta $venta, string $token): void
    {
        abort_unless($venta->origen === 'web', 404);
        abort_unless(
            is_string($venta->token_confirmacion)
                && $venta->token_confirmacion !== ''
                && hash_equals($venta->token_confirmacion, $token),
            404
        );
    }
}
