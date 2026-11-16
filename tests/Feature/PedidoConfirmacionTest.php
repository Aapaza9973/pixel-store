<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\PedidoConfirmacion;
use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PedidoConfirmacionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_el_cliente_confirma_su_pedido_con_el_token(): void
    {
        $venta = $this->crearPedido();

        $response = $this->get(route('pedido.confirmar', [
            'venta' => $venta->id,
            'token' => $venta->token_confirmacion,
        ]));

        $response->assertOk();
        $response->assertSee('Pedido confirmado');
        $this->assertNotNull($venta->fresh()->confirmado_en);
    }

    public function test_un_token_invalido_devuelve_404(): void
    {
        $venta = $this->crearPedido();

        $this->get(route('pedido.confirmar', [
            'venta' => $venta->id,
            'token' => str_repeat('x', 48),
        ]))->assertNotFound();

        $this->assertNull($venta->fresh()->confirmado_en);
    }

    public function test_el_cliente_cancela_su_pedido_con_el_token(): void
    {
        $venta = $this->crearPedido();

        $response = $this->get(route('pedido.cancelar', [
            'venta' => $venta->id,
            'token' => $venta->token_confirmacion,
        ]));

        $response->assertOk();
        $response->assertSee('Pedido cancelado');
        $this->assertSame('Cancelada', $venta->fresh()->estado);
    }

    public function test_el_correo_incluye_los_enlaces_con_token(): void
    {
        $venta = $this->crearPedido();

        $html = (new PedidoConfirmacion($venta))->render();

        $this->assertStringContainsString('Confirmar pedido', $html);
        $this->assertStringContainsString($venta->token_confirmacion, $html);
    }

    private function crearPedido(): Venta
    {
        return Venta::create([
            'fecha' => now(),
            'total' => 100,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'puntos_canjeados' => 0,
            'estado' => 'Pendiente',
            'origen' => 'web',
            'token_confirmacion' => bin2hex(random_bytes(24)),
        ]);
    }
}
