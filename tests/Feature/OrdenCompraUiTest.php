<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\OrdenCompraService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * HU-3.4.10: listado y alta de órdenes de compra desde el panel.
 */
class OrdenCompraUiTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Proveedor $proveedor;

    private Producto $monitor;

    private Producto $teclado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $this->proveedor = Proveedor::create([
            'nombre' => 'Proveedor UI Compras',
            'tipo' => 'Mayorista',
            'descuento_habitual' => 0,
            'activo' => true,
        ]);

        $categoria = Categoria::create(['nombre' => 'Categoría UI Compras', 'tipo' => 'Periférico']);

        $this->monitor = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Monitor UI Compras',
            'precio_unitario' => 900,
            'stock' => 0,
            'umbral_alerta' => 1,
        ]);

        $this->teclado = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Teclado UI Compras',
            'precio_unitario' => 1200,
            'stock' => 0,
            'umbral_alerta' => 1,
        ]);
    }

    public function test_admin_ve_el_listado_de_ordenes(): void
    {
        $orden = app(OrdenCompraService::class)->crear(
            $this->proveedor,
            $this->admin,
            [
                ['producto_id' => $this->monitor->id, 'cantidad' => 1, 'precio_unitario' => 900],
            ],
            ['impuestos' => 10]
        );

        $this->actingAs($this->admin)
            ->get(route('admin.compras.index'))
            ->assertOk()
            ->assertSee($orden->numero)
            ->assertSee($this->proveedor->nombre)
            ->assertSee('Borrador');
    }

    public function test_admin_crea_orden_desde_el_formulario(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.compras.store'), [
            'proveedor_id' => $this->proveedor->id,
            'fecha_entrega' => now()->addDays(5)->toDateString(),
            'impuestos' => 40,
            'notas' => 'Compra de reposición',
            'items' => [
                ['producto_id' => $this->monitor->id, 'cantidad' => 2, 'precio_unitario' => 500],
                ['producto_id' => $this->teclado->id, 'cantidad' => 1, 'precio_unitario' => 1200],
            ],
        ]);

        $orden = OrdenCompra::query()->firstOrFail();

        $response->assertRedirect(route('admin.compras.show', $orden));
        $response->assertSessionHas('success');

        $this->assertSame('borrador', $orden->estado);
        $this->assertSame($this->proveedor->id, $orden->proveedor_id);
        $this->assertSame(2200.0, (float) $orden->subtotal);
        $this->assertSame(2240.0, (float) $orden->total);
        $this->assertSame(2, $orden->detalles()->count());

        $this->assertDatabaseHas('orden_compra_items', [
            'orden_compra_id' => $orden->id,
            'producto_id' => $this->monitor->id,
            'cantidad' => 2,
        ]);
    }
}
