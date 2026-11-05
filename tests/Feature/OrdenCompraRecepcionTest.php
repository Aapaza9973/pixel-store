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
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
