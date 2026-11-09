<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CotizacionUiTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
    }

    public function test_admin_puede_ver_el_listado_de_cotizaciones(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.cotizaciones.index'));

        $response->assertOk();
        $response->assertSee('Cotizaciones');
        $response->assertSee('+ Nueva Cotización');
        $response->assertSee(route('admin.cotizaciones.create'), false);
    }

    public function test_admin_puede_ver_el_formulario_de_creacion(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.cotizaciones.create'));

        $response->assertOk();
        $response->assertSee('Nueva cotización');
        $response->assertSee('Cliente');
        $response->assertSee('Ítems');
        $response->assertSee('Guardar cotización');
    }
}
