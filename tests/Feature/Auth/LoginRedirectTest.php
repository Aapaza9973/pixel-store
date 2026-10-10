<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    private function login(string $email, string $password = 'password'): TestResponse
    {
        return $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    // ============ Redirect por rol (sin intended) ============

    public function test_admin_redirige_a_dashboard_tras_login(): void
    {
        $this->login('admin@pixelstore.com')->assertRedirect('/dashboard');
    }

    public function test_inventario_redirige_a_inventario_tras_login(): void
    {
        $this->login('inventario@pixelstore.com')->assertRedirect('/admin/inventario');
    }

    public function test_vendedor_redirige_a_dashboard_tras_login(): void
    {
        $this->login('vendedor@pixelstore.com')->assertRedirect('/dashboard');
    }

    public function test_cajero_redirige_a_dashboard_tras_login(): void
    {
        $this->login('cajero@pixelstore.com')->assertRedirect('/dashboard');
    }

    // ============ intended (deep links) ============

    public function test_intended_url_se_respeta_para_admin(): void
    {
        // Sin autenticación, /admin/usuarios guarda la URL pendiente.
        $this->get('/admin/usuarios');

        $this->login('admin@pixelstore.com')->assertRedirect('/admin/usuarios');
    }

    public function test_intended_url_se_respeta_para_inventario(): void
    {
        $this->get('/admin/inventario');

        $this->login('inventario@pixelstore.com')->assertRedirect('/admin/inventario');
    }

    public function test_intended_url_de_dashboard_se_respeta(): void
    {
        // intended() gana sobre panelHome(): Inventario va a /dashboard, no a su módulo.
        $this->get('/dashboard');

        $this->login('inventario@pixelstore.com')->assertRedirect('/dashboard');
    }

    // ============ Casos límite (comportamiento actual) ============

    public function test_usuario_inactivo_es_expulsado_al_acceder_al_panel(): void
    {
        $inactivo = User::factory()->create(['activo' => false]);

        // El login acepta las credenciales; el bloqueo ocurre en el middleware user.active.
        $this->login($inactivo->email)->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_usuario_sin_rol_accede_al_dashboard_pero_no_al_admin(): void
    {
        $sinRol = User::factory()->create();

        $this->login($sinRol->email)->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk();
        $this->get('/admin/productos')->assertForbidden();
    }
}
