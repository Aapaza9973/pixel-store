<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PagoPedidoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_el_pedido_muestra_la_pantalla_de_pago_simulada(): void
    {
        $venta = $this->crearPedido();

        $this->get(route('pedido.pagar.form', [
            'venta' => $venta->id,
            'token' => $venta->token_confirmacion,
        ]))
            ->assertOk()
            ->assertSee('Modo simulación');
    }

    public function test_el_pago_simulado_marca_el_pedido_como_pagado(): void
    {
        $venta = $this->crearPedido();

        $this->post(route('pedido.pagar', [
            'venta' => $venta->id,
            'token' => $venta->token_confirmacion,
        ]))->assertOk();

        $this->assertSame('Pagado', $venta->fresh()->estado);
        $this->assertDatabaseHas('pagos', ['venta_id' => $venta->id, 'estado' => 'Completado']);
        $this->assertDatabaseHas('ingresos', ['venta_id' => $venta->id]);
    }

    public function test_un_token_invalido_no_permite_pagar(): void
    {
        $venta = $this->crearPedido();

        $this->post(route('pedido.pagar', [
            'venta' => $venta->id,
            'token' => str_repeat('z', 48),
        ]))->assertNotFound();

        $this->assertSame('Pendiente', $venta->fresh()->estado);
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
