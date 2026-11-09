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

class VentaAnulacionTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Anulación', 'tipo' => 'Componente']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto Para Anular',
            'precio_unitario' => 150,
            'stock' => 5,
            'umbral_alerta' => 1,
        ]);
    }

    private function crearVentaPagada(int $cantidad = 2): Venta
    {
        $venta = Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'fecha' => now(),
            'user_id' => $this->admin->id,
            'cliente_id' => null,
            'total' => 150 * $cantidad,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
            'origen' => 'pos',
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => 150,
            'precio_costo' => 100,
            'descuento_item' => 0,
        ]);

        // La venta ya descontó el stock al confirmarse.
        $this->producto->update(['stock' => 5 - $cantidad]);

        return $venta;
    }

    public function test_admin_puede_anular_venta_y_reingresa_stock(): void
    {
        $venta = $this->crearVentaPagada(2);

        $this->assertSame(3, (int) $this->producto->fresh()->stock);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.ventas.anular', $venta));

        $response->assertRedirect(route('admin.ventas.show', $venta));
        $response->assertSessionHas('success');

        $this->assertSame('Cancelada', $venta->fresh()->estado);
        $this->assertSame(5, (int) $this->producto->fresh()->stock);

        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $this->producto->id,
            'tipo' => 'devolucion',
            'cantidad' => 2,
            'motivo' => 'Anulación Venta #'.$venta->id,
        ]);
    }

    public function test_vendedor_no_puede_anular_venta(): void
    {
        $venta = $this->crearVentaPagada(1);

        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $response = $this->actingAs($vendedor)
            ->post(route('admin.ventas.anular', $venta));

        $response->assertForbidden();
        $this->assertSame('Pagado', $venta->fresh()->estado);
    }
}
