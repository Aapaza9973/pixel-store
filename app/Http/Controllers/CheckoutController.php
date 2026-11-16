<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\CheckoutRequest;
use App\Mail\PedidoConfirmacion;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Checkout del catálogo público (HU-2.3).
 *
 * El carrito vive en localStorage; acá solo se recibe el pedido, se crea la
 * venta Pendiente con origen web y se muestra el número al comprador.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly VentaService $ventas
    ) {}

    public function index(): View
    {
        return view('catalogo.checkout');
    }

    /**
     * El carrito envía JSON (`Accept: application/json`); el formulario también
     * funciona sin JS y en ese caso redirige con `back()`.
     */
    public function store(CheckoutRequest $request): JsonResponse|RedirectResponse
    {
        $datos = $request->validated();

        $items = array_map(static fn (array $item): array => [
            'producto_id' => (int) $item['producto_id'],
            'cantidad' => (int) $item['cantidad'],
        ], $datos['items']);

        $operacionId = trim((string) $request->input('operacion_id', ''));

        try {
            $venta = $this->ventas->crearPedidoOnline(
                $items,
                [
                    'nombre' => $datos['nombre'],
                    'nit_ci' => $datos['nit_ci'],
                    'telefono' => $datos['telefono'],
                    'email' => $datos['email'],
                    'direccion' => $datos['direccion'],
                ],
                $operacionId !== '' ? $operacionId : (string) Str::uuid()
            );
        } catch (StockInsuficienteException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'code' => 'stock_insuficiente',
                ], 409);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->enviarConfirmacion($venta);

        // URL firmada: el pedido contiene datos del comprador y el id es
        // secuencial, así que no debe poder enumerarse.
        $destino = URL::signedRoute('catalogo.gracias', ['venta' => $venta->id]);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => $destino,
                'pedido' => $venta->id,
            ], 201);
        }

        return redirect()->to($destino);
    }

    /**
     * Correo con los enlaces de confirmar/cancelar (HU-3.3). Un fallo de mail
     * no debe romper el checkout: se reporta y el pedido queda creado.
     */
    private function enviarConfirmacion(Venta $venta): void
    {
        try {
            $venta->loadMissing('cliente');
            $email = $venta->cliente?->email;

            if (! is_string($email) || $email === '' || $venta->token_confirmacion === null) {
                return;
            }

            Mail::to($email)->send(new PedidoConfirmacion($venta));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function gracias(Request $request, Venta $venta): View
    {
        abort_unless($venta->origen === 'web', 404);

        $venta->load(['detalles.producto', 'cliente']);

        return view('catalogo.gracias', compact('venta'));
    }
}
