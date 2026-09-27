<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\StockUbicacion;
use App\Models\Ubicacion;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UbicacionTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function crearUbicacion(array $cambios = []): Ubicacion
    {
        return Ubicacion::create(array_merge([
            'nombre' => 'Tienda',
            'tipo' => 'tienda',
            'direccion' => 'Mostrador de ventas',
            'activa' => true,
        ], $cambios));
    }

    public function test_admin_puede_ver_listado(): void
    {
        $ubicacion = $this->crearUbicacion([
            'pasillo' => 'Pasillo A', 'estante' => 'Estante 1',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.ubicaciones.index'));

        $response->assertOk();
        $response->assertSee('Tienda · Pasillo A · Estante 1');
        $response->assertSee($ubicacion->direccion);
    }

    public function test_admin_puede_crear_ubicacion(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.ubicaciones.store'), [
            'nombre' => 'Depósito',
            'tipo' => 'deposito',
            'direccion' => 'Almacén principal',
            'pasillo' => 'Pasillo 1',
            'estante' => 'Estante A',
            'anaquel' => 'Anaquel 1',
            'activa' => '1',
        ]);

        $response->assertRedirect(route('admin.ubicaciones.index'));
        $this->assertDatabaseHas('ubicaciones', [
            'nombre' => 'Depósito',
            'tipo' => 'deposito',
            'pasillo' => 'Pasillo 1',
            'estante' => 'Estante A',
            'anaquel' => 'Anaquel 1',
            'activa' => true,
        ]);
    }

    public function test_nombre_completo_incluye_pasillo_estante_anaquel(): void
    {
        $completa = $this->crearUbicacion([
            'pasillo' => 'Pasillo A', 'estante' => 'Estante 1', 'anaquel' => 'Anaquel 1',
        ]);

        $this->assertSame('Tienda · Pasillo A · Estante 1 · Anaquel 1', $completa->nombre_completo);

        $simple = $this->crearUbicacion([
            'nombre' => 'Tienda · Mostrador', 'pasillo' => null, 'estante' => null, 'anaquel' => null,
        ]);

        $this->assertSame('Tienda · Mostrador', $simple->nombre_completo);
    }

    public function test_admin_puede_editar_ubicacion(): void
    {
        $ubicacion = $this->crearUbicacion();

        $response = $this->actingAs($this->admin)->put(route('admin.ubicaciones.update', $ubicacion), [
            'nombre' => 'Tienda',
            'tipo' => 'tienda',
            'direccion' => 'Nueva dirección',
            'pasillo' => 'Pasillo B',
            'estante' => 'Estante 9',
            'anaquel' => null,
            'activa' => '1',
        ]);

        $response->assertRedirect(route('admin.ubicaciones.index'));
        $this->assertDatabaseHas('ubicaciones', [
            'id' => $ubicacion->id,
            'direccion' => 'Nueva dirección',
            'pasillo' => 'Pasillo B',
            'estante' => 'Estante 9',
        ]);
    }

    public function test_admin_puede_desactivar_ubicacion(): void
    {
        $ubicacion = $this->crearUbicacion(['activa' => true]);

        $response = $this->actingAs($this->admin)->put(route('admin.ubicaciones.update', $ubicacion), [
            'nombre' => 'Tienda',
            'tipo' => 'tienda',
            'direccion' => 'Mostrador de ventas',
            // Sin campo 'activa': el checkbox desmarcado debe desactivarla.
        ]);

        $response->assertRedirect(route('admin.ubicaciones.index'));
        $this->assertFalse($ubicacion->fresh()->activa);
    }

    public function test_no_puede_eliminar_ubicacion_con_stock(): void
    {
        $ubicacion = $this->crearUbicacion();

        $categoria = Categoria::create(['nombre' => 'Cat Ubicación', 'tipo' => 'Componente']);
        $marca = Marca::create(['nombre' => 'Marca Ubicación']);

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Producto con stock',
            'precio_unitario' => 100,
            'stock' => 5,
            'umbral_alerta' => 2,
            'sku' => 'SKU-UBIC-001',
            'visible_catalogo' => true,
        ]);

        StockUbicacion::create([
            'producto_id' => $producto->id,
            'ubicacion_id' => $ubicacion->id,
            'cantidad' => 5,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.ubicaciones.destroy', $ubicacion));

        $response->assertRedirect(route('admin.ubicaciones.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ubicaciones', ['id' => $ubicacion->id]);
    }
}
