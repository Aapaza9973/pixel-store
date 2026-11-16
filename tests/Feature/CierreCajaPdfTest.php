<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CierreCajaPdfTest extends TestCase
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

    public function test_admin_descarga_el_pdf_del_cierre(): void
    {
        $cierre = $this->crearCierre($this->admin);

        $response = $this->actingAs($this->admin)->get(route('admin.caja.pdf', $cierre));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_usuario_sin_rol_de_caja_no_puede_descargar_el_pdf(): void
    {
        $cierre = $this->crearCierre($this->admin);

        $this->actingAs($this->vendedor)
            ->get(route('admin.caja.pdf', $cierre))
            ->assertForbidden();
    }

    private function crearCierre(User $usuario): CierreCaja
    {
        return CierreCaja::create([
            'user_id' => $usuario->id,
            'fecha' => now()->toDateString(),
            'turno' => 'mañana',
            'efectivo_esperado' => 500,
            'efectivo_real' => 480,
            'diferencia' => -20,
        ]);
    }
}
