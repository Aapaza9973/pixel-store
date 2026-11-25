<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DomainException;
use App\Models\Categoria;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraRecepcionItem;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\OrdenCompraService;
use App\Services\VentaService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrdenCompraRecepcionTest extends TestCase
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
            'nombre' => 'Recepción Hardware',
            'tipo' => 'Mayorista',
            'descuento_habitual' => 0,
            'activo' => true,
        ]);

        $categoria = Categoria::create([
            'nombre' => 'Categoría Recepción OC',
            'tipo' => 'Periférico',
        ]);

        $this->teclado = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Teclado Recepción',
            'precio_unitario' => 200,
            'stock' => 0,
            'umbral_alerta' => 1,
        ]);

        $this->mouse = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Mouse Recepción',
            'precio_unitario' => 100,
            'stock' => 0,
            'umbral_alerta' => 1,
        ]);
    }

    /**
     * Crea una OC de 2 teclados y 1 mouse, y la deja 'enviada' para recibirla.
     */
    private function ordenEnviada(): OrdenCompra
    {
        $orden = $this->servicio->crear($this->proveedor, $this->inventario, [
            ['producto_id' => $this->teclado->id, 'cantidad' => 2, 'precio_unitario' => 150.50],
            ['producto_id' => $this->mouse->id, 'cantidad' => 1, 'precio_unitario' => 99.99],
        ]);

        $orden->update(['estado' => 'enviada']);

        return $orden->refresh();
    }

    /**
     * @return array<int, int> producto_id => orden_compra_item_id
     */
    private function lineasPorProducto(OrdenCompra $orden): array
    {
        $lineas = [];

        foreach ($orden->detalles()->get() as $linea) {
            $lineas[$linea->producto_id] = $linea->id;
        }

        return $lineas;
    }

    /**
     * Deuda del Sprint 2 (`docs/12`, acción #3): la recepción existía en el
     * servicio pero no se probaba por la ruta que usa el modal del detalle.
     */
    public function test_recepcion_desde_la_ui_suma_stock_y_marca_recibida_total(): void
    {
        $orden = $this->ordenEnviada();
        $lineas = $this->lineasPorProducto($orden);

        $this->actingAs($this->inventario)
            ->post(route('admin.compras.recibir', $orden), [
                'items' => [
                    ['orden_compra_item_id' => $lineas[$this->teclado->id], 'cantidad' => 2],
                    ['orden_compra_item_id' => $lineas[$this->mouse->id], 'cantidad' => 1],
                    ['orden_compra_item_id' => $lineas[$this->mouse->id], 'cantidad' => 0],
                ],
            ])
            ->assertRedirect(route('admin.compras.show', $orden))
            ->assertSessionHas('success');

        $this->assertSame('recibida_total', $orden->refresh()->estado);
        $this->assertSame(2, $this->teclado->refresh()->stock);
        $this->assertSame(1, $this->mouse->refresh()->stock);
    }

    public function test_recibir_todo_marca_recibida_total_y_suma_stock(): void
    {
        $orden = $this->ordenEnviada();
        $lineas = $this->lineasPorProducto($orden);

        $this->servicio->recibir($orden, $this->inventario, [
            ['orden_compra_item_id' => $lineas[$this->teclado->id], 'cantidad' => 2],
            ['orden_compra_item_id' => $lineas[$this->mouse->id], 'cantidad' => 1],
        ]);

        $this->assertSame('recibida_total', $orden->refresh()->estado);
        $this->assertSame(2, $this->teclado->refresh()->stock);
        $this->assertSame(1, $this->mouse->refresh()->stock);
    }

    public function test_recibir_parcial_marca_recibida_parcial(): void
    {
        $orden = $this->ordenEnviada();
        $lineas = $this->lineasPorProducto($orden);

        $this->servicio->recibir($orden, $this->inventario, [
            ['orden_compra_item_id' => $lineas[$this->teclado->id], 'cantidad' => 1],
        ]);

        $this->assertSame('recibida_parcial', $orden->refresh()->estado);
        $this->assertSame(1, $this->teclado->refresh()->stock);
        $this->assertSame(0, $this->mouse->refresh()->stock);
    }

    public function test_no_permite_recibir_mas_de_lo_pedido(): void
    {
        $orden = $this->ordenEnviada();
        $lineas = $this->lineasPorProducto($orden);

        $this->servicio->recibir($orden, $this->inventario, [
            ['orden_compra_item_id' => $lineas[$this->teclado->id], 'cantidad' => 1],
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Solo falta 1 unidad por recibir de Teclado Recepción.');

        $this->servicio->recibir($orden, $this->inventario, [
            ['orden_compra_item_id' => $lineas[$this->teclado->id], 'cantidad' => 2],
        ]);
    }

    public function test_item_de_otra_orden_es_rechazado(): void
    {
        $orden = $this->ordenEnviada();
        $otra = $this->ordenEnviada();
        $lineaAjena = $this->lineasPorProducto($otra)[$this->teclado->id];

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Una de las líneas no pertenece a esta orden.');

        $this->servicio->recibir($orden, $this->inventario, [
            ['orden_compra_item_id' => $lineaAjena, 'cantidad' => 1],
        ]);
    }

    public function test_recepcion_persiste_cantidades_por_linea(): void
    {
        $orden = $this->ordenEnviada();
        $lineas = $this->lineasPorProducto($orden);

        $recepcion = $this->servicio->recibir($orden, $this->inventario, [
            ['orden_compra_item_id' => $lineas[$this->teclado->id], 'cantidad' => 2],
            ['orden_compra_item_id' => $lineas[$this->mouse->id], 'cantidad' => 1],
        ], ['notas' => 'Llegó completo']);

        $this->assertSame($orden->id, $recepcion->orden_id);
        $this->assertSame('Llegó completo', $recepcion->notas);
        $this->assertCount(2, $recepcion->items);

        $this->assertDatabaseHas('orden_compra_recepcion_items', [
            'recepcion_id' => $recepcion->id,
            'orden_compra_item_id' => $lineas[$this->teclado->id],
            'cantidad' => 2,
        ]);
        $this->assertDatabaseHas('orden_compra_recepcion_items', [
            'recepcion_id' => $recepcion->id,
            'orden_compra_item_id' => $lineas[$this->mouse->id],
            'cantidad' => 1,
        ]);
        $this->assertSame(2, OrdenCompraRecepcionItem::where('recepcion_id', $recepcion->id)->count());
    }

    public function test_recepcion_actualiza_el_costo_con_promedio_ponderado(): void
    {
        $this->teclado->update(['stock' => 2, 'costo' => 100]);

        $orden = $this->ordenEnviada();
        $lineas = $this->lineasPorProducto($orden);

        $this->servicio->recibir($orden, $this->inventario, [
            ['orden_compra_item_id' => $lineas[$this->teclado->id], 'cantidad' => 2],
        ]);

        // (2 × 100 + 2 × 150,50) / 4 = 125,25
        $this->assertEqualsWithDelta(125.25, (float) $this->teclado->refresh()->costo, 0.001);
        $this->assertSame(4, (int) $this->teclado->stock);
    }

    public function test_recepcion_parcial_pondera_solo_las_unidades_recibidas(): void
    {
        $this->teclado->update(['stock' => 2, 'costo' => 100]);

        $orden = $this->ordenEnviada();
        $lineas = $this->lineasPorProducto($orden);

        $this->servicio->recibir($orden, $this->inventario, [
            ['orden_compra_item_id' => $lineas[$this->teclado->id], 'cantidad' => 1],
        ]);

        // (2 × 100 + 1 × 150,50) / 3 = 116,8333… → 116,83
        $this->assertEqualsWithDelta(116.83, (float) $this->teclado->refresh()->costo, 0.001);
        $this->assertSame(3, (int) $this->teclado->stock);
    }

    public function test_recepcion_sin_stock_previo_toma_el_precio_de_la_linea(): void
    {
        $orden = $this->ordenEnviada();
        $lineas = $this->lineasPorProducto($orden);

        $this->servicio->recibir($orden, $this->inventario, [
            ['orden_compra_item_id' => $lineas[$this->teclado->id], 'cantidad' => 2],
            ['orden_compra_item_id' => $lineas[$this->mouse->id], 'cantidad' => 1],
        ]);

        $this->assertEqualsWithDelta(150.50, (float) $this->teclado->refresh()->costo, 0.001);
        $this->assertEqualsWithDelta(99.99, (float) $this->mouse->refresh()->costo, 0.001);
    }

    public function test_recepcion_con_costo_cero_toma_el_precio_de_la_linea(): void
    {
        // `costo = 0` significa "sin costo cargado": no debe diluir el promedio.
        $this->teclado->update(['stock' => 2, 'costo' => 0]);

        $orden = $this->ordenEnviada();
        $lineas = $this->lineasPorProducto($orden);

        $this->servicio->recibir($orden, $this->inventario, [
            ['orden_compra_item_id' => $lineas[$this->teclado->id], 'cantidad' => 2],
        ]);

        $this->assertEqualsWithDelta(150.50, (float) $this->teclado->refresh()->costo, 0.001);
    }

    public function test_recepcion_no_reescribe_el_costo_de_una_venta_anterior(): void
    {
        $this->teclado->update(['stock' => 4, 'costo' => 100]);

        $venta = app(VentaService::class)->confirmar(
            [['producto_id' => $this->teclado->id, 'cantidad' => 1]],
            [['metodo' => 'Efectivo', 'monto' => 200]],
            [
                'vendedor_id' => $this->inventario->id,
                'operacion_id' => (string) Str::uuid(),
            ]
        );

        $lineaVendida = $venta->detalles()->firstOrFail();
        $this->assertEqualsWithDelta(100.0, (float) $lineaVendida->precio_costo, 0.001);

        $orden = $this->ordenEnviada();
        $lineas = $this->lineasPorProducto($orden);

        $this->servicio->recibir($orden, $this->inventario, [
            ['orden_compra_item_id' => $lineas[$this->teclado->id], 'cantidad' => 2],
        ]);

        // Stock previo 3 (4 − 1 vendida) a Bs 100 + 2 a Bs 150,50 → 120,20.
        $this->assertEqualsWithDelta(120.20, (float) $this->teclado->refresh()->costo, 0.001);

        // La utilidad de una venta pasada no se recalcula: usa el costo congelado.
        $this->assertEqualsWithDelta(100.0, (float) $lineaVendida->refresh()->precio_costo, 0.001);
    }
}
