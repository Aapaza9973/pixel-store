<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Vista de error 403 personalizada (tarea 1.1.14).
 *
 * Verifica el mensaje contextual, el botón adaptativo (dashboard/inicio)
 * y que la vista sea autónoma (no depende de layouts.app ni de sesión).
 */
class ErrorPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_vista_403_personalizada_se_muestra_sin_rol(): void
    {
        $sinRol = User::factory()->create();

        $response = $this->actingAs($sinRol)->get(route('admin.usuarios.index'));

        $response->assertForbidden();
        $response->assertSee('Acceso denegado');
        $response->assertSee('No tiene permisos asignados');
        $response->assertSee('Volver al dashboard');
    }

    public function test_vista_403_muestra_mensaje_generico_sin_permiso(): void
    {
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        // Ruta protegida por policy (no dispara el middleware user.has.role).
        $response = $this->actingAs($vendedor)->get(route('admin.usuarios.create'));

        $response->assertForbidden();
        $response->assertSee('Error 403');
        $response->assertSee('Acceso denegado');
        $response->assertSee('Volver al dashboard');
        $response->assertSee('No tienes permiso para acceder a esta sección.');
        $response->assertDontSee('This action is unauthorized.');
    }

    public function test_vista_403_para_invitado_ofrece_ir_al_inicio(): void
    {
        $this->assertGuest();

        $response = $this->view('errors.403', [
            'exception' => new HttpException(403, 'Sección restringida'),
        ]);

        $response->assertSee('Acceso denegado');
        $response->assertSee('Sección restringida');
        $response->assertSee('Ir al inicio');
        $response->assertDontSee('Volver al dashboard');
    }
}
