<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventarioAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_admin_puede_acceder_a_inventario(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $this->actingAs($admin)->get('/admin/inventario')->assertOk();
    }

    public function test_inventario_puede_acceder_a_inventario(): void
    {
        $inventario = User::where('email', 'inventario@pixelstore.com')->firstOrFail();

        $this->actingAs($inventario)->get('/admin/inventario')->assertOk();
    }

    public function test_vendedor_no_puede_acceder_a_inventario(): void
    {
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $this->actingAs($vendedor)->get('/admin/inventario')->assertForbidden();
    }

    public function test_cajero_no_puede_acceder_a_inventario(): void
    {
        $cajero = User::where('email', 'cajero@pixelstore.com')->firstOrFail();

        $this->actingAs($cajero)->get('/admin/inventario')->assertForbidden();
    }

    public function test_cliente_no_puede_acceder_a_inventario(): void
    {
        $cliente = User::factory()->create();
        $cliente->assignRole('Cliente');

        $this->actingAs($cliente)->get('/admin/inventario')->assertForbidden();
    }

    public function test_usuario_sin_rol_no_puede_acceder_a_inventario(): void
    {
        $sinRol = User::factory()->create();

        $this->actingAs($sinRol)->get('/admin/inventario')->assertForbidden();
    }

    public function test_acceso_denegado_al_inventario_registra_auditoria(): void
    {
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $this->actingAs($vendedor)->get('/admin/inventario')->assertForbidden();

        $this->assertDatabaseHas('logs_auditoria', [
            'accion' => 'acceso_denegado_inventario',
            'user_id' => $vendedor->id,
        ]);

        $log = LogAuditoria::where('accion', 'acceso_denegado_inventario')
            ->where('user_id', $vendedor->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('admin/inventario', $log->datos_nuevos['ruta_intentada'] ?? null);
    }

    public function test_acceso_permitido_al_inventario_no_registra_auditoria(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $this->actingAs($admin)->get('/admin/inventario')->assertOk();

        $this->assertDatabaseMissing('logs_auditoria', [
            'accion' => 'acceso_denegado_inventario',
            'user_id' => $admin->id,
        ]);
    }
}
