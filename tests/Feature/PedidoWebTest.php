<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentaService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class PedidoWebTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Pedidos Web', 'tipo' => 'Componente']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Notebook Pedido Web 14"',
            'precio_unitario' => 150,
            'stock' => 5,
            'umbral_alerta' => 1,
        ]);
    }

    private function crearPedido(int $cantidad = 2): Venta
    {
        return app(VentaService::class)->crearPedidoOnline(
            [['producto_id' => $this->producto->id, 'cantidad' => $cantidad]],
            [
                'nombre' => 'Ana Compradora',
                'nit_ci' => '7654321',
                'telefono' => '71234567',
                'email' => 'ana@example.com',
                'direccion' => 'Av. Siempre Viva 742',
            ],
            (string) Str::uuid()
        );
    }

    public function test_admin_puede_ver_pedidos_web_pendientes(): void
    {
        $pedido = $this->crearPedido();

        $numero = '#'.str_pad((string) $pedido->id, 6, '0', STR_PAD_LEFT);

        $response = $this->actingAs($this->admin)->get(route('admin.pedidos-web.index'));

        $response->assertOk();
        $response->assertSee('Pedidos web');
        $response->assertSee($numero);
        $response->assertSee('Ana Compradora');
        $response->assertSee('ana@example.com');
        $response->assertSee('Pendiente');
    }

    public function test_admin_puede_marcar_pedido_web_como_pagado(): void
    {
        $pedido = $this->crearPedido();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.pedidos-web.pagar', $pedido));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('Pagado', $pedido->fresh()->estado);

        $this->assertDatabaseHas('ingresos', [
            'venta_id' => $pedido->id,
            'user_id' => $this->admin->id,
            'monto' => 300,
            'tipo' => 'venta',
        ]);
    }

    public function test_cancelar_pedido_web_devuelve_stock(): void
    {
        $pedido = $this->crearPedido(2);

        // La reserva del pedido ya descontó las 2 unidades.
        $this->assertSame(3, (int) $this->producto->fresh()->stock);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.pedidos-web.cancelar', $pedido));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('Cancelada', $pedido->fresh()->estado);
        $this->assertSame(5, (int) $this->producto->fresh()->stock);

        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $this->producto->id,
            'tipo' => 'devolucion',
            'cantidad' => 2,
            'motivo' => 'Cancelación Pedido Web #'.$pedido->id,
        ]);
    }
}
