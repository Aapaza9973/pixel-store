<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LogAuditoria;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Historial de auditoría individual de un usuario.
 *
 * Ruta `admin.usuarios.historial`: antes de crear la vista devolvía 500.
 */
class UserHistorialTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Usuario administrador demo.
     */
    private function admin(): User
    {
        return User::where('email', 'admin@pixelstore.com')->firstOrFail();
    }

    /**
     * Usuario vendedor demo.
     */
    private function vendedor(): User
    {
        return User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
    }

    /**
     * Crea un evento de auditoría cuyo modelo auditado es el usuario indicado.
     */
    private function crearLog(User $usuario, string $accion = 'editar_user'): void
    {
        LogAuditoria::create([
            'user_id' => $this->admin()->id,
            'accion' => $accion,
            'modelo' => User::class,
            'modelo_id' => $usuario->id,
            'datos_anteriores' => ['name' => 'Nombre anterior'],
            'datos_nuevos' => ['name' => 'Nombre nuevo'],
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);
    }

    /**
     * Limpia los eventos previos del usuario para assertions deterministas.
     */
    private function limpiarLogs(User $usuario): void
    {
        LogAuditoria::where('modelo', User::class)
            ->where('modelo_id', $usuario->id)
            ->delete();
    }

    public function test_admin_puede_ver_historial_de_usuario(): void
    {
        $vendedor = $this->vendedor();

        $this->limpiarLogs($vendedor);
        $this->crearLog($vendedor);

        // Evento de OTRO usuario: no debe aparecer en este historial.
        $this->crearLog(User::factory()->create(), 'crear_user');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.usuarios.historial', $vendedor));

        $response->assertOk();
        $response->assertSee('Historial de accesos');
        $response->assertSee($vendedor->email);
        $response->assertSee('editar_user');
        $response->assertDontSee('crear_user');
    }

    public function test_vendedor_no_puede_ver_historial_de_usuario(): void
    {
        $response = $this->actingAs($this->vendedor())
            ->get(route('admin.usuarios.historial', $this->admin()));

        $response->assertForbidden();
    }

    public function test_historial_pagina_con_20_eventos_por_pagina(): void
    {
        $vendedor = $this->vendedor();

        $this->limpiarLogs($vendedor);

        for ($i = 0; $i < 25; $i++) {
            $this->crearLog($vendedor);
        }

        $pagina1 = $this->actingAs($this->admin())
            ->get(route('admin.usuarios.historial', $vendedor));

        $pagina1->assertOk();
        $this->assertSame(20, $pagina1->viewData('logs')->count());
        $this->assertSame(25, $pagina1->viewData('logs')->total());

        $pagina2 = $this->actingAs($this->admin())
            ->get(route('admin.usuarios.historial', ['user' => $vendedor, 'page' => 2]));

        $pagina2->assertOk();
        $this->assertSame(5, $pagina2->viewData('logs')->count());
    }
}
