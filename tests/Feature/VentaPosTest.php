<?php

declare(strict_types=1);

namespace Tests\Feature;

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
}
