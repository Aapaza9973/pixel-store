<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VentaPosTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
    }

    public function test_admin_puede_ver_el_punto_de_venta(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.ventas.create'));

        $response->assertOk();
        $response->assertSee('Nueva venta');
        $response->assertSee('El carrito está vacío');
    }

    public function test_vendedor_puede_ver_el_punto_de_venta(): void
    {
        $response = $this->actingAs($this->vendedor)->get(route('admin.ventas.create'));

        $response->assertOk();
    }

    public function test_el_layout_incluye_el_componente_de_carrito(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.ventas.create'));

        $response->assertSee('carrito()', false);
        $response->assertSee('Continuar al pago');
        $response->assertSee('Saldo pendiente');
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $response = $this->get(route('admin.ventas.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_pos_muestra_buscador_de_productos(): void
    {
        $response = $this->actingAs($this->vendedor)->get(route('admin.ventas.create'));

        $response->assertOk();
        $response->assertSee('Buscar producto');
        $response->assertSee('id="producto-busqueda"', false);
        $response->assertSee('Nombre, SKU o código de barras');
    }

    public function test_buscar_productos_requiere_permiso_ver_productos(): void
    {
        $cliente = User::factory()->create(['activo' => true]);
        $cliente->assignRole('Cliente');

        $this->actingAs($cliente)
            ->getJson(route('admin.ventas.buscar-productos', ['q' => 'Laptop']))
            ->assertForbidden();
    }

    public function test_pos_oculta_cliente_rapido_para_cajero(): void
    {
        // El Cajero atiende en caja: ve clientes, pero no los crea.
        $cajero = User::where('email', 'cajero@pixelstore.com')->firstOrFail();

        $this->actingAs($this->vendedor)
            ->get(route('admin.ventas.create'))
            ->assertOk()
            ->assertSee('Cliente rápido');

        $this->actingAs($cajero)
            ->get(route('admin.ventas.create'))
            ->assertOk()
            ->assertDontSee('Cliente rápido');
    }

    public function test_buscar_productos_incluye_maneja_numero_serie(): void
    {
        $categoria = Categoria::create(['nombre' => 'Categoría POS Serie', 'tipo' => 'Componente']);

        Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Laptop POS Serie',
            'precio_unitario' => 5000,
            'stock' => 3,
            'umbral_alerta' => 1,
            'maneja_numero_serie' => true,
        ]);

        Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Mouse POS Serie',
            'precio_unitario' => 100,
            'stock' => 10,
            'umbral_alerta' => 1,
            'maneja_numero_serie' => false,
        ]);

        $response = $this->actingAs($this->vendedor)
            ->getJson(route('admin.ventas.buscar-productos', ['q' => 'POS Serie']));

        $response->assertOk();
        // El endpoint ordena por nombre: Laptop antes que Mouse.
        $response->assertJsonPath('data.0.maneja_numero_serie', true);
        $response->assertJsonPath('data.1.maneja_numero_serie', false);
    }
}
