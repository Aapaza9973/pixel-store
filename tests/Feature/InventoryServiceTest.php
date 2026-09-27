<?php

namespace Tests\Feature;

use App\Exceptions\StockInsuficienteException;
use App\Models\AlertaStock;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\StockUbicacion;
use App\Models\Ubicacion;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\CategoriaSeeder;
use Database\Seeders\MarcaSeeder;
use Database\Seeders\RoleSeeder;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use DatabaseTransactions;

    private InventoryService $service;

    private User $admin;

    private Categoria $categoria;

    private Marca $marca;

    private Ubicacion $tienda;

    private Ubicacion $deposito;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(CategoriaSeeder::class);
        $this->seed(MarcaSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $this->categoria = Categoria::where('nombre', 'Procesadores')->firstOrFail();
        $this->marca = Marca::where('nombre', 'AMD')->firstOrFail();

        $this->tienda = Ubicacion::create(['nombre' => 'Tienda Test', 'tipo' => 'tienda', 'activa' => true]);
        $this->deposito = Ubicacion::create(['nombre' => 'Depósito Test', 'tipo' => 'deposito', 'activa' => true]);

        $this->service = app(InventoryService::class);
    }

    private function crearProducto(int $stock = 0, int $umbral = 5): Producto
    {
        return Producto::create([
            'categoria_id' => $this->categoria->id,
            'marca_id' => $this->marca->id,
            'nombre' => 'Producto '.uniqid(),
            'precio_unitario' => 100,
            'costo' => 80,
            'stock' => $stock,
            'umbral_alerta' => $umbral,
            'sku' => 'SKU-'.uniqid(),
            'visible_catalogo' => true,
        ]);
    }

    /**
     * Producto con stock total y el mismo stock reflejado en la tienda.
     */
    private function crearProductoEnUbicacion(int $cantidad, int $umbral = 5): Producto
    {
        $producto = $this->crearProducto($cantidad, $umbral);

        StockUbicacion::create([
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'cantidad' => $cantidad,
        ]);

        return $producto;
    }

    public function test_entrada_aumenta_stock_y_registra_movimiento(): void
    {
        $producto = $this->crearProducto(0, 5);

        $movimiento = $this->service->ajustarStock(
            $producto, 10, 'entrada', 'Compra a proveedor', $this->tienda->id, $this->admin->id
        );

        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame(10, $movimiento->cantidad);
        $this->assertSame(10, $movimiento->stock_resultante);

        $this->assertDatabaseHas('movimientos_stock', [
            'id' => $movimiento->id,
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'tipo' => 'entrada',
            'cantidad' => 10,
            'stock_resultante' => 10,
            'motivo' => 'Compra a proveedor',
            'user_id' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'cantidad' => 10,
        ]);
    }

    public function test_salida_reduce_stock_y_registra_movimiento(): void
    {
        $producto = $this->crearProductoEnUbicacion(10, 5);

        $movimiento = $this->service->ajustarStock(
            $producto, -4, 'salida', 'Venta mostrador', $this->tienda->id, $this->admin->id
        );

        $this->assertSame(6, $producto->fresh()->stock);
        $this->assertSame(-4, $movimiento->cantidad);
        $this->assertSame(6, $movimiento->stock_resultante);

        $this->assertDatabaseHas('movimientos_stock', [
            'id' => $movimiento->id,
            'producto_id' => $producto->id,
            'tipo' => 'salida',
            'cantidad' => -4,
            'stock_resultante' => 6,
        ]);

        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'cantidad' => 6,
        ]);
    }

    public function test_salida_rechaza_stock_insuficiente_con_exception(): void
    {
        $producto = $this->crearProducto(3, 5);

        $this->expectException(StockInsuficienteException::class);
        $this->expectExceptionMessage('Stock insuficiente para');

        $this->service->ajustarStock($producto, -5, 'salida', null, null, $this->admin->id);
    }

    public function test_stock_nunca_queda_negativo(): void
    {
        $producto = $this->crearProducto(0, 5);

        try {
            $this->service->ajustarStock($producto, -1, 'salida');
            $this->fail('Se esperaba StockInsuficienteException.');
        } catch (StockInsuficienteException) {
            // Comportamiento esperado.
        }

        $this->assertSame(0, $producto->fresh()->stock);
        $this->assertSame(0, MovimientoStock::where('producto_id', $producto->id)->count());
    }

    public function test_genera_alerta_bajo_stock_cuando_cruza_umbral(): void
    {
        $producto = $this->crearProductoEnUbicacion(10, 5);

        $this->service->ajustarStock($producto, -6, 'salida', null, $this->tienda->id, $this->admin->id);

        $this->assertSame(4, $producto->fresh()->stock);
        $this->assertDatabaseHas('alertas_stock', [
            'producto_id' => $producto->id,
            'tipo' => 'bajo_stock',
            'leida' => false,
        ]);
    }

    public function test_genera_alerta_sin_stock_cuando_llega_a_cero(): void
    {
        $producto = $this->crearProductoEnUbicacion(3, 5);

        $this->service->ajustarStock($producto, -3, 'salida', null, $this->tienda->id, $this->admin->id);

        $this->assertSame(0, $producto->fresh()->stock);
        $this->assertDatabaseHas('alertas_stock', [
            'producto_id' => $producto->id,
            'tipo' => 'sin_stock',
            'leida' => false,
        ]);
    }

    public function test_no_duplica_alerta_sin_leer(): void
    {
        $producto = $this->crearProductoEnUbicacion(10, 5);

        $this->service->ajustarStock($producto, -6, 'salida', null, $this->tienda->id, $this->admin->id);
        $this->service->ajustarStock($producto, -1, 'salida', null, $this->tienda->id, $this->admin->id);

        $this->assertSame(1, AlertaStock::where('producto_id', $producto->id)
            ->where('tipo', 'bajo_stock')
            ->where('leida', false)
            ->count());
    }

    public function test_movimiento_tiene_stock_resultante_correcto(): void
    {
        $producto = $this->crearProducto(0, 5);

        $this->service->ajustarStock($producto, 5, 'entrada', null, $this->tienda->id);
        $segundo = $this->service->ajustarStock($producto, 3, 'entrada', null, $this->tienda->id);

        $this->assertSame(8, $segundo->stock_resultante);
        $this->assertSame(8, $producto->fresh()->stock);
    }

    public function test_transferencia_mueve_stock_entre_ubicaciones(): void
    {
        $producto = $this->crearProductoEnUbicacion(10, 5);

        $this->service->transferir(
            $producto, $this->tienda->id, $this->deposito->id, 4, $this->admin->id
        );

        $this->assertSame(10, $producto->fresh()->stock);

        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'cantidad' => 6,
        ]);

        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->deposito->id,
            'cantidad' => 4,
        ]);

        $this->assertSame(2, MovimientoStock::where('producto_id', $producto->id)->count());

        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id,
            'tipo' => 'salida',
            'ubicacion_id' => $this->tienda->id,
        ]);

        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id,
            'tipo' => 'entrada',
            'ubicacion_id' => $this->deposito->id,
        ]);

        $movimiento = MovimientoStock::where('producto_id', $producto->id)->firstOrFail();
        $this->assertStringContainsString('Transferencia', $movimiento->motivo);
    }

    public function test_sincronizar_stock_total_suma_ubicaciones(): void
    {
        $producto = $this->crearProducto(999, 5);

        StockUbicacion::create(['producto_id' => $producto->id, 'ubicacion_id' => $this->tienda->id, 'cantidad' => 3]);
        StockUbicacion::create(['producto_id' => $producto->id, 'ubicacion_id' => $this->deposito->id, 'cantidad' => 4]);

        $this->service->sincronizarStockTotal($producto);

        $this->assertSame(7, $producto->fresh()->stock);
    }

    public function test_ajuste_actualiza_stock_ubicacion_si_se_especifica(): void
    {
        $producto = $this->crearProducto(5, 5);

        $this->service->ajustarStock($producto, 7, 'ajuste', 'Ajuste de inventario', $this->tienda->id);

        $this->assertSame(12, $producto->fresh()->stock);
        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'cantidad' => 7,
        ]);
    }

    public function test_stock_insuficiente_lanza_domain_exception(): void
    {
        $producto = $this->crearProducto(2, 5);

        $this->expectException(DomainException::class);

        $this->service->ajustarStock($producto, -3, 'salida');
    }

    public function test_ubicacion_sin_unidades_lanza_exception(): void
    {
        // El producto tiene stock total, pero el depósito no tiene unidades.
        $producto = $this->crearProductoEnUbicacion(5, 5);

        $this->expectException(StockInsuficienteException::class);
        $this->expectExceptionMessage('Stock insuficiente en la ubicación');

        $this->service->ajustarStock($producto, -5, 'salida', 'Traslado', $this->deposito->id);
    }

    public function test_hay_stock_suficiente_refleja_el_stock_actual(): void
    {
        $producto = $this->crearProductoEnUbicacion(4, 5);

        $this->assertTrue($this->service->hayStockSuficiente($producto, 4));
        $this->assertFalse($this->service->hayStockSuficiente($producto, 5));
    }
}
