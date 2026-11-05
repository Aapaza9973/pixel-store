<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProveedorTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $vendedor;

    private User $inventario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
        $this->inventario = User::where('email', 'inventario@pixelstore.com')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function crearProveedor(array $cambios = []): Proveedor
    {
        return Proveedor::create(array_merge([
            'nombre' => 'Tech Import SRL',
            'nit' => '123456789',
            'contacto' => 'Mario Pérez',
            'telefono' => '+591 70000000',
            'email' => 'ventas@techimport.bo',
            'direccion' => 'Av. Arce 123',
            'tipo' => 'Importador',
            'plazos_pago' => '30 días',
            'tiempo_entrega' => '7 días hábiles',
            'descuento_habitual' => 5,
            'activo' => true,
        ], $cambios));
    }

    public function test_admin_puede_ver_listado(): void
    {
        $this->crearProveedor(['nombre' => 'Andes Hardware']);

        $response = $this->actingAs($this->admin)->get(route('admin.proveedores.index'));

        $response->assertOk();
        $response->assertSee('Andes Hardware');
    }

    public function test_admin_puede_crear_proveedor(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.proveedores.store'), [
            'nombre' => 'Nuevo Mayorista',
            'nit' => '987654321',
            'tipo' => 'Mayorista',
            'plazos_pago' => '60 días',
            'tiempo_entrega' => '15 días',
            'descuento_habitual' => 10,
            'activo' => '1',
        ]);

        $response->assertRedirect(route('admin.proveedores.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('proveedores', [
            'nombre' => 'Nuevo Mayorista',
            'tipo' => 'Mayorista',
            'activo' => true,
        ]);
    }

    public function test_admin_puede_editar_proveedor(): void
    {
        $proveedor = $this->crearProveedor();

        $response = $this->actingAs($this->admin)->put(route('admin.proveedores.update', $proveedor), [
            'nombre' => 'Tech Import Actualizado',
            'tipo' => 'Distribuidor',
            'descuento_habitual' => 12.5,
        ]);

        $response->assertRedirect(route('admin.proveedores.index'));
        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'nombre' => 'Tech Import Actualizado',
            'tipo' => 'Distribuidor',
            'activo' => false,
        ]);
    }

    public function test_admin_puede_eliminar_proveedor(): void
    {
        $proveedor = $this->crearProveedor();

        $response = $this->actingAs($this->admin)->delete(route('admin.proveedores.destroy', $proveedor));

        $response->assertRedirect(route('admin.proveedores.index'));
        $this->assertDatabaseMissing('proveedores', ['id' => $proveedor->id]);
    }

    public function test_valida_nombre_y_tipo_obligatorios(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.proveedores.store'), [
            'nombre' => '',
            'tipo' => 'Inexistente',
        ]);

        $response->assertSessionHasErrors(['nombre', 'tipo']);
    }

    public function test_vendedor_no_puede_crear_proveedor(): void
    {
        $response = $this->actingAs($this->vendedor)->post(route('admin.proveedores.store'), [
            'nombre' => 'No autorizado',
            'tipo' => 'Local',
        ]);

        $response->assertForbidden();
    }

    public function test_inventario_no_puede_eliminar_proveedor(): void
    {
        $proveedor = $this->crearProveedor();

        $response = $this->actingAs($this->inventario)->delete(route('admin.proveedores.destroy', $proveedor));

        $response->assertForbidden();
        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id]);
    }
}
