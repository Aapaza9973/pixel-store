<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_checkout_muestra_resumen(): void
    {
        $response = $this->get(route('catalogo.checkout'));

        $response->assertOk();
        $response->assertSee('Datos de entrega');
        $response->assertSee('Resumen del pedido');

        // El resumen se arma en el cliente desde localStorage: se pinea el wiring.
        $response->assertSee('x-text="formato(subtotal)"', false);
        $response->assertSee('name="nit_ci"', false);
    }

    public function test_pagina_gracias_muestra_numero(): void
    {
        $categoria = Categoria::create(['nombre' => 'Categoría Checkout', 'tipo' => 'Componente']);

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Monitor Checkout 27"',
            'precio_unitario' => 1200,
            'stock' => 4,
            'umbral_alerta' => 1,
        ]);

        $venta = app(VentaService::class)->crearPedidoOnline(
            [['producto_id' => $producto->id, 'cantidad' => 1]],
            [
                'nombre' => 'Ana Compradora',
                'nit_ci' => '7654321',
                'telefono' => '71234567',
                'email' => 'ana@example.com',
                'direccion' => 'Av. Siempre Viva 742',
            ],
            (string) Str::uuid()
        );

        $response = $this->get(URL::signedRoute('catalogo.gracias', ['venta' => $venta->id]));

        $response->assertOk();
        $response->assertSee('Pedido #'.str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT).' recibido');
        $response->assertSee('Monitor Checkout 27"');
        $response->assertSee('Bs 1.200,00');
    }
}
