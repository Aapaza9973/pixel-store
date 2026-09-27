<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductoTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $vendedor;

    private Categoria $categoria;

    private Marca $marca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $this->categoria = Categoria::create(['nombre' => 'Categoría Test', 'tipo' => 'Componente']);
        $this->marca = Marca::create(['nombre' => 'Marca Test']);
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datosProducto(array $cambios = []): array
    {
        return array_merge([
            'categoria_id' => $this->categoria->id,
            'marca_id' => $this->marca->id,
            'nombre' => 'Producto de prueba',
            'descripcion' => 'Descripción de prueba',
            'precio_unitario' => 1000,
            'costo' => 800,
            'stock' => 10,
            'umbral_alerta' => 5,
            'sku' => 'SKU-TEST-001',
            'codigo_barras' => '123456789',
            'visible_catalogo' => '1',
        ], $cambios);
    }

    public function test_admin_puede_ver_listado(): void
    {
        $producto = Producto::create($this->datosProducto());

        $response = $this->actingAs($this->admin)->get(route('admin.productos.index'));

        $response->assertOk();
        $response->assertSee($producto->nombre);
    }

    public function test_admin_puede_ver_formularios(): void
    {
        $producto = Producto::create($this->datosProducto());

        $this->actingAs($this->admin)->get(route('admin.productos.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.productos.edit', $producto))->assertOk();
    }

    public function test_admin_puede_crear_producto(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.productos.store'), $this->datosProducto(['nombre' => 'Producto Nuevo']));

        $response->assertRedirect(route('admin.productos.index'));
        $this->assertDatabaseHas('productos', [
            'nombre' => 'Producto Nuevo',
            'sku' => 'SKU-TEST-001',
        ]);
    }

    public function test_admin_puede_editar_producto(): void
    {
        $producto = Producto::create($this->datosProducto());

        $response = $this->actingAs($this->admin)
            ->put(route('admin.productos.update', $producto), $this->datosProducto([
                'nombre' => 'Producto Editado',
                'precio_unitario' => 1500,
            ]));

        $response->assertRedirect(route('admin.productos.index'));
        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'nombre' => 'Producto Editado',
            'precio_unitario' => 1500,
        ]);
    }

    public function test_admin_puede_eliminar_producto(): void
    {
        $producto = Producto::create($this->datosProducto());

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.productos.destroy', $producto));

        $response->assertRedirect(route('admin.productos.index'));
        $this->assertDatabaseMissing('productos', ['id' => $producto->id]);
    }

    public function test_no_puede_crear_producto_con_nombre_duplicado(): void
    {
        Producto::create($this->datosProducto(['nombre' => 'Duplicado', 'sku' => 'SKU-DUP-001']));

        $response = $this->actingAs($this->admin)
            ->post(route('admin.productos.store'), $this->datosProducto([
                'nombre' => 'Duplicado',
                'sku' => 'SKU-DUP-002',
            ]));

        $response->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('productos', 1);
    }

    public function test_vendedor_no_puede_crear_producto(): void
    {
        $response = $this->actingAs($this->vendedor)
            ->post(route('admin.productos.store'), $this->datosProducto());

        $response->assertForbidden();
        $this->assertDatabaseCount('productos', 0);
    }
}
