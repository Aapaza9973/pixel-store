<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\StockUbicacion;
use App\Models\Ubicacion;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UbicacionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductoStockTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Categoria $categoria;

    private Marca $marca;

    private Ubicacion $tienda;

    private Ubicacion $deposito;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(UbicacionSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $this->categoria = Categoria::create(['nombre' => 'Cat Stock Test', 'tipo' => 'Componente']);
        $this->marca = Marca::create(['nombre' => 'Marca Stock Test']);

        $this->tienda = Ubicacion::where('tipo', 'tienda')->orderBy('id')->firstOrFail();
        $this->deposito = Ubicacion::where('tipo', 'deposito')->orderBy('id')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datos(array $cambios = []): array
    {
        $unico = Str::uuid()->toString();

        return array_merge([
            'categoria_id' => $this->categoria->id,
            'marca_id' => $this->marca->id,
            'nombre' => "Producto Stock {$unico}",
            'descripcion' => 'Descripción de prueba',
            'precio_unitario' => 1000,
            'costo' => 800,
            'stock' => 0,
            'umbral_alerta' => 5,
            'sku' => 'SKU-STOCK-'.$unico,
            'codigo_barras' => (string) random_int(100000000, 999999999),
            'visible_catalogo' => '1',
        ], $cambios);
    }

    /**
     * Crea un producto mediante el endpoint store y devuelve el modelo.
     */
    private function crearViaStore(int $stock, ?int $ubicacionId = null): Producto
    {
        $this->actingAs($this->admin)
            ->post(route('admin.productos.store'), $this->datos([
                'stock' => $stock,
                'ubicacion_id' => $ubicacionId,
            ]))
            ->assertRedirect(route('admin.productos.index'));

        return Producto::query()->latest('id')->firstOrFail();
    }

    /**
     * Actualiza el stock mediante el endpoint update.
     */
    private function actualizar(Producto $producto, int $stock, ?int $ubicacionId = null): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.productos.update', $producto), $this->datos([
                'nombre' => $producto->nombre,
                'stock' => $stock,
                'ubicacion_id' => $ubicacionId,
            ]))
            ->assertRedirect(route('admin.productos.index'));
    }

    public function test_store_crea_stock_en_la_ubicacion_seleccionada(): void
    {
        $producto = $this->crearViaStore(8, $this->deposito->id);

        $this->assertSame(8, $producto->fresh()->stock);

        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->deposito->id,
            'cantidad' => 8,
        ]);

        $this->assertDatabaseMissing('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
        ]);

        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->deposito->id,
            'tipo' => 'entrada',
            'cantidad' => 8,
            'stock_resultante' => 8,
            'motivo' => 'Stock inicial',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_store_usa_tienda_por_defecto_cuando_no_se_envia_ubicacion(): void
    {
        $producto = $this->crearViaStore(5);

        $this->assertSame(5, $producto->fresh()->stock);

        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'cantidad' => 5,
        ]);

        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'tipo' => 'entrada',
            'cantidad' => 5,
        ]);
    }

    public function test_store_con_stock_cero_no_genera_movimiento_ni_ubicacion(): void
    {
        $producto = $this->crearViaStore(0, $this->tienda->id);

        $this->assertSame(0, $producto->fresh()->stock);
        $this->assertSame(0, MovimientoStock::where('producto_id', $producto->id)->count());
        $this->assertSame(0, StockUbicacion::where('producto_id', $producto->id)->count());
    }

    public function test_store_rechaza_ubicacion_inexistente(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.productos.store'), $this->datos([
            'stock' => 1,
            'ubicacion_id' => 999999,
        ]));

        $response->assertSessionHasErrors('ubicacion_id');
        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('movimientos_stock', 0);
    }

    public function test_update_aumenta_stock_en_la_ubicacion(): void
    {
        $producto = $this->crearViaStore(5, $this->tienda->id);

        $this->actualizar($producto, 12, $this->tienda->id);

        $this->assertSame(12, $producto->fresh()->stock);

        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'cantidad' => 12,
        ]);

        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'tipo' => 'ajuste',
            'cantidad' => 7,
            'stock_resultante' => 12,
            'motivo' => 'Ajuste manual desde edición',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_update_reduce_stock_en_la_ubicacion(): void
    {
        $producto = $this->crearViaStore(10, $this->tienda->id);

        $this->actualizar($producto, 4, $this->tienda->id);

        $this->assertSame(4, $producto->fresh()->stock);

        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'cantidad' => 4,
        ]);

        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $producto->id,
            'tipo' => 'ajuste',
            'cantidad' => -6,
            'stock_resultante' => 4,
        ]);
    }

    public function test_update_permite_aplicar_el_ajuste_a_otra_ubicacion(): void
    {
        $producto = $this->crearViaStore(10, $this->tienda->id);

        $this->actualizar($producto, 15, $this->deposito->id);

        $this->assertSame(15, $producto->fresh()->stock);

        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'cantidad' => 10,
        ]);

        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->deposito->id,
            'cantidad' => 5,
        ]);

        $this->assertSame(
            15,
            (int) StockUbicacion::where('producto_id', $producto->id)->sum('cantidad')
        );
    }

    public function test_update_usa_tienda_por_defecto_sin_ubicacion(): void
    {
        $producto = $this->crearViaStore(5, $this->tienda->id);

        $this->actualizar($producto, 9);

        $this->assertSame(9, $producto->fresh()->stock);

        $this->assertDatabaseHas('stock_ubicacion', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $this->tienda->id,
            'cantidad' => 9,
        ]);
    }

    public function test_update_sin_cambio_de_stock_no_registra_movimiento(): void
    {
        $producto = $this->crearViaStore(6, $this->tienda->id);

        // Único movimiento: la entrada de stock inicial.
        $this->assertSame(1, MovimientoStock::where('producto_id', $producto->id)->count());

        $this->actualizar($producto, 6, $this->tienda->id);

        $this->assertSame(6, $producto->fresh()->stock);
        $this->assertSame(1, MovimientoStock::where('producto_id', $producto->id)->count());
    }

    public function test_update_no_permite_stock_negativo(): void
    {
        $producto = $this->crearViaStore(6, $this->tienda->id);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.productos.update', $producto), $this->datos([
                'nombre' => $producto->nombre,
                'stock' => -1,
                'ubicacion_id' => $this->tienda->id,
            ]));

        $response->assertSessionHasErrors('stock');

        $this->assertSame(6, $producto->fresh()->stock);
        $this->assertSame(1, MovimientoStock::where('producto_id', $producto->id)->count());
    }

    public function test_stock_total_igual_a_suma_de_ubicaciones_tras_varias_ediciones(): void
    {
        $producto = $this->crearViaStore(10, $this->tienda->id);

        $this->actualizar($producto, 7, $this->tienda->id);
        $this->actualizar($producto, 12, $this->tienda->id);

        $totalUbicaciones = (int) StockUbicacion::where('producto_id', $producto->id)->sum('cantidad');

        $this->assertSame(12, (int) $producto->fresh()->stock);
        $this->assertSame($totalUbicaciones, (int) $producto->fresh()->stock);
    }
}
