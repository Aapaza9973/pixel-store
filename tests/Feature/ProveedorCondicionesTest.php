<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProveedorCondicionesTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
    }

    public function test_crear_proveedor_con_datos_comerciales(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.proveedores.store'), [
            'nombre' => 'Andes Comercial SRL',
            'nit' => '556677889',
            'tipo' => 'Distribuidor',
            'plazos_pago' => '45 días',
            'tiempo_entrega' => '10 días hábiles',
            'descuento_habitual' => 7.5,
            'activo' => '1',
        ]);

        $response->assertRedirect(route('admin.proveedores.index'));
        $this->assertDatabaseHas('proveedores', [
            'nombre' => 'Andes Comercial SRL',
            'plazos_pago' => '45 días',
            'tiempo_entrega' => '10 días hábiles',
            'descuento_habitual' => 7.5,
        ]);
    }

    public function test_descuento_habitual_rechaza_mayor_a_100(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.proveedores.create'))
            ->post(route('admin.proveedores.store'), [
                'nombre' => 'Proveedor Inválido',
                'tipo' => 'Local',
                'descuento_habitual' => 150,
            ]);

        $response->assertSessionHasErrors('descuento_habitual');
        $this->assertDatabaseMissing('proveedores', ['nombre' => 'Proveedor Inválido']);
    }

    public function test_show_muestra_condiciones_comerciales(): void
    {
        $proveedor = Proveedor::create([
            'nombre' => 'Tecno Bolivia',
            'nit' => '112233445',
            'tipo' => 'Mayorista',
            'plazos_pago' => '30 días',
            'tiempo_entrega' => '5 días hábiles',
            'descuento_habitual' => 12.5,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.proveedores.show', $proveedor));

        $response->assertOk();
        $response->assertSee('Condiciones comerciales');
        $response->assertSee('30 días');
        $response->assertSee('5 días hábiles');
        $response->assertSee('12,50');
    }
}
