<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class DevolucionControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Devolución Controller', 'tipo' => 'Componente']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Teclado Devolución Controller',
            'precio_unitario' => 100,
            'stock' => 3,
            'umbral_alerta' => 1,
        ]);
    }

    private function crearVenta(int $cantidad): Venta
    {
        $venta = Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'user_id' => $this->vendedor->id,
            'total' => 100 * $cantidad,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => 100,
        ]);

        return $venta;
    }

    public function test_store_crea_devolucion_y_reingresa_stock(): void
    {
        $venta = $this->crearVenta(2);
        $detalle = $venta->detalles()->firstOrFail();

        $this->actingAs($this->vendedor)
            ->postJson(route('admin.ventas.devoluciones.store', $venta), [
                'operacion_id' => (string) Str::uuid(),
                'motivo' => 'Producto defectuoso',
                'items' => [
                    ['venta_detalle_id' => $detalle->id, 'cantidad' => 2],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('venta_estado', 'totalmente_devuelta');

        $this->assertSame(5, (int) $this->producto->fresh()->stock);
        $this->assertDatabaseHas('devoluciones', ['venta_id' => $venta->id, 'estado' => 'procesada']);
    }

    public function test_show_muestra_historial_y_badge_de_estado(): void
    {
        $venta = $this->crearVenta(3);
        $detalle = $venta->detalles()->firstOrFail();

        $this->actingAs($this->vendedor)
            ->postJson(route('admin.ventas.devoluciones.store', $venta), [
                'operacion_id' => (string) Str::uuid(),
                'motivo' => 'Cambio de talla',
                'items' => [
                    ['venta_detalle_id' => $detalle->id, 'cantidad' => 1],
                ],
            ])
            ->assertCreated();

        $this->actingAs($this->vendedor)
            ->get(route('admin.ventas.show', $venta))
            ->assertOk()
            ->assertSee('Parcialmente devuelta')
            ->assertSee('Cambio de talla')
            ->assertSee('Devolución #');
    }
}
