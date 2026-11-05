<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DomainException;
use App\Models\Categoria;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\OrdenCompraService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrdenCompraServiceTest extends TestCase
{
    use DatabaseTransactions;

    private User $inventario;

    private Proveedor $proveedor;

    private Producto $teclado;

    private Producto $mouse;

    private OrdenCompraService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->inventario = User::where('email', 'inventario@pixelstore.com')->firstOrFail();
        $this->servicio = app(OrdenCompraService::class);

        $this->proveedor = Proveedor::create([
            'nombre' => 'Andes Hardware',
            'tipo' => 'Mayorista',
            'descuento_habitual' => 0,
            'activo' => true,
        ]);

        $categoria = Categoria::create([
            'nombre' => 'Categoría Órdenes de Compra',
            'tipo' => 'Periférico',
        ]);

        $this->teclado = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Teclado Órdenes',
            'precio_unitario' => 200,
            'stock' => 0,
            'umbral_alerta' => 1,
        ]);

        $this->mouse = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Mouse Órdenes',
            'precio_unitario' => 100,
            'stock' => 0,
            'umbral_alerta' => 1,
        ]);
    }

    /**
     * @return array<int, array{producto_id:int, cantidad:int, precio_unitario:float}>
     */
    private function items(): array
    {
        return [
            ['producto_id' => $this->teclado->id, 'cantidad' => 2, 'precio_unitario' => 150.50],
            ['producto_id' => $this->mouse->id, 'cantidad' => 1, 'precio_unitario' => 99.99],
        ];
    }

    public function test_calcular_totales_suma_los_items_y_aplica_impuestos(): void
    {
        $totales = $this->servicio->calcularTotales($this->items(), 40);

        $this->assertCount(2, $totales['lineas']);

        $this->assertSame($this->teclado->id, $totales['lineas'][0]['producto_id']);
        $this->assertSame(2, $totales['lineas'][0]['cantidad']);
        $this->assertEqualsWithDelta(301.00, $totales['lineas'][0]['subtotal'], 0.001);

        $this->assertEqualsWithDelta(99.99, $totales['lineas'][1]['subtotal'], 0.001);

        $this->assertEqualsWithDelta(400.99, $totales['subtotal'], 0.001);
        $this->assertEqualsWithDelta(40.0, $totales['impuestos'], 0.001);
        $this->assertEqualsWithDelta(440.99, $totales['total'], 0.001);
    }

    public function test_crear_orden_persiste_cabecera_y_detalles_con_totales(): void
    {
        $orden = $this->servicio->crear($this->proveedor, $this->inventario, $this->items(), [
            'impuestos' => 20,
            'fecha_entrega' => now()->addDays(7)->toDateString(),
            'notas' => 'Reposición de periféricos',
        ]);

        $this->assertTrue($orden->exists);
        $this->assertSame($this->proveedor->id, (int) $orden->proveedor_id);
        $this->assertSame($this->inventario->id, (int) $orden->user_id);
        $this->assertSame('borrador', $orden->estado);
        $this->assertSame(now()->toDateString(), $orden->fecha_emision->toDateString());

        $this->assertDatabaseHas('ordenes_compra', [
            'id' => $orden->id,
            'numero' => $orden->numero,
            'estado' => 'borrador',
            'subtotal' => 400.99,
            'impuestos' => 20,
            'total' => 420.99,
            'notas' => 'Reposición de periféricos',
        ]);

        $this->assertCount(2, $orden->detalles);
        $this->assertTrue($orden->detalles->first()->relationLoaded('producto'));

        $this->assertDatabaseHas('orden_compra_items', [
            'orden_compra_id' => $orden->id,
            'producto_id' => $this->teclado->id,
            'cantidad' => 2,
            'subtotal' => 301.00,
        ]);

        // 3.4.7 es Día 3: crear la orden no toca stock.
        $this->assertSame(0, (int) $this->teclado->fresh()->stock);
    }

    public function test_crear_orden_genera_numero_correlativo_unico(): void
    {
        $primera = $this->servicio->crear($this->proveedor, $this->inventario, $this->items());
        $segunda = $this->servicio->crear($this->proveedor, $this->inventario, $this->items());

        $prefijo = 'OC-'.now()->year.'-';

        $this->assertMatchesRegularExpression('/^OC-\d{4}-\d{4}$/', $primera->numero);
        $this->assertStringStartsWith($prefijo, $primera->numero);
        $this->assertSame($prefijo, substr($segunda->numero, 0, strlen($prefijo)));

        $sufijoPrimera = (int) substr($primera->numero, strlen($prefijo));
        $sufijoSegunda = (int) substr($segunda->numero, strlen($prefijo));

        $this->assertSame($sufijoPrimera + 1, $sufijoSegunda);
        $this->assertSame(1, OrdenCompra::where('numero', $primera->numero)->count());
        $this->assertSame(1, OrdenCompra::where('numero', $segunda->numero)->count());
    }

    public function test_crear_orden_rechaza_sin_items(): void
    {
        $antes = OrdenCompra::count();

        try {
            $this->servicio->crear($this->proveedor, $this->inventario, []);

            $this->fail('Se esperaba DomainException: la orden necesita al menos un ítem.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('al menos un ítem', $e->getMessage());
        }

        $this->assertSame($antes, OrdenCompra::count());
    }

    public function test_policy_permite_admin_e_inventario_y_rechaza_vendedor(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
        $orden = $this->servicio->crear($this->proveedor, $this->inventario, $this->items());

        foreach ([$admin, $this->inventario] as $usuario) {
            $this->assertTrue($usuario->can('viewAny', OrdenCompra::class));
            $this->assertTrue($usuario->can('view', $orden));
            $this->assertTrue($usuario->can('create', OrdenCompra::class));
            $this->assertTrue($usuario->can('update', $orden));
            $this->assertTrue($usuario->can('delete', $orden));
        }

        $this->assertFalse($vendedor->can('viewAny', OrdenCompra::class));
        $this->assertFalse($vendedor->can('view', $orden));
        $this->assertFalse($vendedor->can('create', OrdenCompra::class));
        $this->assertFalse($vendedor->can('update', $orden));
        $this->assertFalse($vendedor->can('delete', $orden));
    }
}
