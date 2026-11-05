<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * HU-6.5 (6.5.1-6.5.3): exportación PDF de reportes.
 */
class ReportePdfTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'fecha' => '2026-03-02 10:00:00',
            'user_id' => $this->admin->id,
            'total' => 150,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
        ]);
    }

    public function test_pdf_periodo_se_genera_correctamente(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.reportes.pdf', [
            'tipo' => 'periodo',
            'desde' => '2026-03-01',
            'hasta' => '2026-03-31',
            'agrupacion' => 'dia',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'reporte-periodo-2026-03-01-2026-03-31.pdf',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_pdf_no_permite_vendedor(): void
    {
        // Cliente no tiene el permiso 'ver reportes'.
        $cliente = User::factory()->create([
            'email_verified_at' => now(),
            'activo' => true,
        ]);
        $cliente->assignRole('Cliente');

        $response = $this->actingAs($cliente)->get(route('admin.reportes.pdf', [
            'tipo' => 'vendedor',
            'desde' => '2026-03-01',
            'hasta' => '2026-03-31',
        ]));

        $response->assertForbidden();
    }
}
