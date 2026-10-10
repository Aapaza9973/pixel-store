<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Verifica que cada caso de bloqueo/error emite un mensaje distinto y en español.
 */
class MensajesContextualesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_inventario_403_muestra_mensaje_especifico(): void
    {
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $this->actingAs($vendedor)
            ->get('/admin/inventario')
            ->assertForbidden()
            ->assertSee('No tiene permisos para acceder al módulo de inventario.');
    }

    public function test_usuario_sin_rol_403_muestra_mensaje_especifico(): void
    {
        $sinRol = User::factory()->create();

        $this->actingAs($sinRol)
            ->get('/admin/usuarios')
            ->assertForbidden()
            ->assertSee('No tiene permisos asignados.');
    }

    public function test_usuario_inactivo_es_expulsado_con_mensaje(): void
    {
        $inactivo = User::factory()->create(['activo' => false]);

        // El login acepta las credenciales; el middleware user.active bloquea al acceder.
        $this->post('/login', ['email' => $inactivo->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->get('/dashboard')
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'Su cuenta está desactivada. Contacte al administrador.');
    }

    public function test_credenciales_invalidas_muestran_mensaje_en_espanol(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-incorrecta',
        ])->assertSessionHasErrors('email');

        $this->assertSame(
            'Estas credenciales no coinciden con nuestros registros.',
            session('errors')->first('email')
        );
    }

    public function test_rate_limit_excedido_muestra_mensaje(): void
    {
        $user = User::factory()->create();
        $credenciales = ['email' => $user->email, 'password' => 'password-incorrecta'];

        // 5 intentos fallidos consumen el límite (5 / 60s por email|IP).
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', $credenciales);
        }

        $response = $this->post('/login', $credenciales);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Demasiados intentos',
            session('errors')->first('email')
        );
    }

    public function test_los_cuatro_mensajes_son_distintos(): void
    {
        $mensajes = [
            'No tiene permisos para acceder al módulo de inventario.',
            'No tiene permisos asignados.',
            'Su cuenta está desactivada. Contacte al administrador.',
            'Estas credenciales no coinciden con nuestros registros.',
            'Demasiados intentos de acceso. Por favor intente nuevamente en 60 segundos.',
        ];

        $this->assertSame(
            count($mensajes),
            count(array_unique($mensajes)),
            'Los mensajes contextuales no deben colisionar entre sí.'
        );

        foreach ($mensajes as $mensaje) {
            $this->assertNotSame('', trim($mensaje));
        }
    }
}
