<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * El checkout reserva stock al crear el pedido Pendiente. Si el comprador
 * abandona, el comando `pedidos:expirar` tiene que devolver esas unidades.
 */
class PedidoWebExpiracionTest extends TestCase
{
    use DatabaseTransactions;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $categoria = Categoria::create(['nombre' => 'Categoría Expiración', 'tipo' => 'Componente']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto Expiración',
            'precio_unitario' => 300,
            'stock' => 10,
            'umbral_alerta' => 1,
            'visible_catalogo' => true,
        ]);
    }

    private function pedido(int $cantidad = 2): Venta
    {
        return app(VentaService::class)->crearPedidoOnline(
            [['producto_id' => $this->producto->id, 'cantidad' => $cantidad]],
            [
                'nombre' => 'Cliente Abandona',
                'nit_ci' => '1122334',
                'telefono' => '70000000',
                'email' => 'abandona@example.com',
                'direccion' => 'Calle Falsa 123',
            ],
            (string) Str::uuid()
        );
    }

    public function test_cancela_pedidos_vencidos_y_devuelve_el_stock(): void
    {
        $venta = $this->pedido(2);

        $this->assertSame(8, (int) $this->producto->fresh()->stock);

        Venta::whereKey($venta->id)->update(['created_at' => now()->subHours(30)]);

        $this->artisan('pedidos:expirar')->assertSuccessful();

        $this->assertSame('Cancelada', $venta->fresh()->estado);
        $this->assertSame(10, (int) $this->producto->fresh()->stock);
    }

    public function test_no_toca_pedidos_recientes(): void
    {
        $venta = $this->pedido(1);

        $this->artisan('pedidos:expirar')->assertSuccessful();

        $this->assertSame('Pendiente', $venta->fresh()->estado);
        $this->assertSame(9, (int) $this->producto->fresh()->stock);
    }

    public function test_el_dry_run_no_cancela_nada(): void
    {
        $venta = $this->pedido(1);

        Venta::whereKey($venta->id)->update(['created_at' => now()->subHours(48)]);

        $this->artisan('pedidos:expirar', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('Pendiente', $venta->fresh()->estado);
        $this->assertSame(9, (int) $this->producto->fresh()->stock);
    }

    public function test_no_toca_pedidos_pagados(): void
    {
        $venta = $this->pedido(1);

        Venta::whereKey($venta->id)->update([
            'created_at' => now()->subHours(48),
            'estado' => 'Pagado',
        ]);

        $this->artisan('pedidos:expirar')->assertSuccessful();

        $this->assertSame('Pagado', $venta->fresh()->estado);
        $this->assertSame(9, (int) $this->producto->fresh()->stock);
    }
}
