<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $vendedor;

    private User $cajero;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
        $this->cajero = User::where('email', 'cajero@pixelstore.com')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function crearCliente(array $cambios = []): Cliente
    {
        return Cliente::create(array_merge([
            'nombre' => 'Ana Torres',
            'email' => 'ana.torres@example.com',
            'telefono' => '+591 70011122',
            'nit_ci' => '7654321',
            'direccion' => 'Calle Sucre 45',
            'activo' => true,
        ], $cambios));
    }

    public function test_admin_puede_ver_listado(): void
    {
        $this->crearCliente(['nombre' => 'Beto Quispe']);

        $response = $this->actingAs($this->admin)->get(route('admin.clientes.index'));

        $response->assertOk();
        $response->assertSee('Beto Quispe');
    }

    public function test_vendedor_puede_crear_cliente(): void
    {
        $response = $this->actingAs($this->vendedor)->post(route('admin.clientes.store'), [
            'nombre' => 'Carla Méndez',
            'email' => 'carla@example.com',
            'nit_ci' => '1234567',
            'telefono' => '+591 70033344',
            'activo' => '1',
        ]);

        $response->assertRedirect(route('admin.clientes.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('clientes', [
            'nombre' => 'Carla Méndez',
            'email' => 'carla@example.com',
            'activo' => true,
        ]);
    }

    public function test_admin_puede_editar_cliente(): void
    {
        $cliente = $this->crearCliente();

        $response = $this->actingAs($this->admin)->put(route('admin.clientes.update', $cliente), [
            'nombre' => 'Ana Torres Actualizada',
            'email' => 'ana.nueva@example.com',
            'nit_ci' => '7654321',
            'activo' => '1',
        ]);

        $response->assertRedirect(route('admin.clientes.index'));
        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombre' => 'Ana Torres Actualizada',
            'email' => 'ana.nueva@example.com',
            'activo' => true,
        ]);
    }

    public function test_admin_puede_eliminar_cliente(): void
    {
        $cliente = $this->crearCliente();

        $response = $this->actingAs($this->admin)->delete(route('admin.clientes.destroy', $cliente));

        $response->assertRedirect(route('admin.clientes.index'));
        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_nit_ci_duplicado_devuelve_error(): void
    {
        $this->crearCliente(['nit_ci' => '9988776']);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.clientes.create'))
            ->post(route('admin.clientes.store'), [
                'nombre' => 'Otro Cliente',
                'nit_ci' => '9988776',
            ]);

        $response->assertSessionHasErrors('nit_ci');
        $this->assertSame(1, Cliente::where('nit_ci', '9988776')->count());
    }

    public function test_email_duplicado_devuelve_error(): void
    {
        $this->crearCliente(['email' => 'repetido@example.com']);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.clientes.create'))
            ->post(route('admin.clientes.store'), [
                'nombre' => 'Otro Cliente',
                'email' => 'repetido@example.com',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, Cliente::where('email', 'repetido@example.com')->count());
    }

    public function test_buscar_clientes_por_nombre(): void
    {
        $this->crearCliente(['nombre' => 'Fernanda Rojas', 'nit_ci' => '1111111']);
        $this->crearCliente(['nombre' => 'Otro Cliente', 'nit_ci' => '2222222', 'email' => 'otro@example.com']);

        $response = $this->actingAs($this->vendedor)
            ->getJson(route('admin.clientes.buscar', ['q' => 'Fernanda']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.nombre', 'Fernanda Rojas');
    }

    public function test_cliente_sin_permiso_no_puede_crear(): void
    {
        // El Cajero atiende en caja: ve y edita clientes, pero no los crea.
        $response = $this->actingAs($this->cajero)->post(route('admin.clientes.store'), [
            'nombre' => 'No autorizado',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('clientes', ['nombre' => 'No autorizado']);
    }

    public function test_buscar_productos_para_pos(): void
    {
        $categoria = Categoria::create(['nombre' => 'Categoría POS', 'tipo' => 'Componente']);

        Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Teclado POS Mecánico',
            'sku' => 'SKU-POS-1',
            'precio_unitario' => 250,
            'stock' => 7,
            'umbral_alerta' => 2,
        ]);

        $response = $this->actingAs($this->vendedor)
            ->getJson(route('admin.ventas.buscar-productos', ['q' => 'Teclado']));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.nombre', 'Teclado POS Mecánico');
        $response->assertJsonPath('data.0.stock', 7);
        $this->assertEquals(250.0, $response->json('data.0.precio_unitario'));
    }
}
