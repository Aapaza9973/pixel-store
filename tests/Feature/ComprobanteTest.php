<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Marca;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ComprobanteTest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    private Venta $venta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $cliente = Cliente::create([
            'nombre' => 'Fernanda Comprobante',
            'nit_ci' => '9988776',
            'telefono' => '+591 70011122',
            'activo' => true,
        ]);

        $categoria = Categoria::create(['nombre' => 'Categoría Comprobante', 'tipo' => 'Componente']);
        $marca = Marca::create(['nombre' => 'Marca Comprobante']);

        $monitor = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Monitor Comprobante',
            'precio_unitario' => 300,
            'stock' => 10,
            'umbral_alerta' => 1,
        ]);

        $teclado = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Teclado Comprobante',
            'precio_unitario' => 500,
            'stock' => 10,
            'umbral_alerta' => 1,
        ]);

        // 2 x 300 + 1 x 500 = 1100; 10 % = 110; total 990.
        $this->venta = Venta::create([
            'fecha' => now(),
            'user_id' => $this->vendedor->id,
            'cliente_id' => $cliente->id,
            'total' => 990,
            'descuento' => 110,
            'descuento_porcentaje' => 10,
            'estado' => 'Pagado',
        ]);

        DetalleVenta::create([
            'venta_id' => $this->venta->id,
            'producto_id' => $monitor->id,
            'cantidad' => 2,
            'precio_unitario' => 300,
        ]);

        DetalleVenta::create([
            'venta_id' => $this->venta->id,
            'producto_id' => $teclado->id,
            'cantidad' => 1,
            'precio_unitario' => 500,
        ]);

        Pago::create([
            'venta_id' => $this->venta->id,
            'monto' => 500,
            'metodo' => 'Efectivo',
            'estado' => 'Completado',
        ]);

        Pago::create([
            'venta_id' => $this->venta->id,
            'monto' => 490,
            'metodo' => 'Tarjeta',
            'estado' => 'Completado',
            'referencia' => 'POS-777',
        ]);
    }

    public function test_comprobante_muestra_los_datos_de_la_venta(): void
    {
        $response = $this->actingAs($this->vendedor)
            ->get(route('admin.ventas.comprobante', $this->venta));

        $response->assertOk();
        $response->assertSee('#'.str_pad((string) $this->venta->id, 6, '0', STR_PAD_LEFT));
        $response->assertSee('Fernanda Comprobante');
        $response->assertSee('TOTAL');
        $response->assertSee('990.00');
        $response->assertSee('POS-777');
    }

    public function test_comprobante_requiere_permiso_ver_ventas(): void
    {
        // Inventario opera el depósito y en RoleSeeder NO tiene "ver ventas".
        $inventario = User::factory()->create(['activo' => true]);
        $inventario->assignRole('Inventario');

        $this->actingAs($inventario)
            ->get(route('admin.ventas.comprobante', $this->venta))
            ->assertForbidden();
    }

    public function test_comprobante_de_venta_inexistente_devuelve_404(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('admin.ventas.comprobante', 999999))
            ->assertNotFound();
    }
}
