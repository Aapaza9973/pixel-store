<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LogAuditoria;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuditoriaPanelTest extends TestCase
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

    public function test_admin_ve_el_listado_de_auditoria(): void
    {
        LogAuditoria::create([
            'user_id' => $this->admin->id,
            'accion' => 'login',
            'modelo' => User::class,
            'modelo_id' => $this->admin->id,
            'ip' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.auditoria.index'));

        $response->assertOk();
        $response->assertSee('Auditoría');
        $response->assertSee('login');
    }

    public function test_filtra_por_accion(): void
    {
        LogAuditoria::create(['accion' => 'accion_filtrada', 'ip' => '127.0.0.1']);
        LogAuditoria::create(['accion' => 'otra_accion', 'ip' => '127.0.0.1']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.auditoria.index', ['accion' => 'accion_filtrada']));

        $response->assertOk();
        $response->assertSee('accion_filtrada');
        $this->assertSame(1, $response->viewData('logs')->total());
    }

    public function test_exporta_csv(): void
    {
        LogAuditoria::create(['accion' => 'login', 'ip' => '127.0.0.1']);

        $response = $this->actingAs($this->admin)->get(route('admin.auditoria.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_vendedor_no_accede_a_la_auditoria(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('admin.auditoria.index'))
            ->assertForbidden();
    }
}
