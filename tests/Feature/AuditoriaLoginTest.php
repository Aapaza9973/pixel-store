<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuditoriaLoginTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_exitoso_registra_en_auditoria(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('logs_auditoria', [
            'accion' => 'login',
            'user_id' => $user->id,
            'modelo' => User::class,
            'modelo_id' => $user->id,
        ]);

        $log = LogAuditoria::where('accion', 'login')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->email, $log->datos_nuevos['email'] ?? null);
    }

    public function test_login_fallido_registra_email_intentado(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'contraseña-incorrecta',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');

        $this->assertDatabaseHas('logs_auditoria', [
            'accion' => 'login_fallido',
            'user_id' => null,
        ]);

        $log = LogAuditoria::where('accion', 'login_fallido')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->email, $log->datos_nuevos['email'] ?? null);
    }

    public function test_logout_registra_en_auditoria(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');

        $this->assertDatabaseHas('logs_auditoria', [
            'accion' => 'logout',
            'user_id' => $user->id,
            'modelo' => User::class,
            'modelo_id' => $user->id,
        ]);

        $log = LogAuditoria::where('accion', 'logout')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->email, $log->datos_nuevos['email'] ?? null);
    }

    public function test_login_fallido_no_expone_password(): void
    {
        $plainPassword = 'super-secret-password-12345';
        $intentadoEmail = 'noexiste@pixelstore.com';

        $response = $this->post('/login', [
            'email' => $intentadoEmail,
            'password' => $plainPassword,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');

        $log = LogAuditoria::where('accion', 'login_fallido')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($intentadoEmail, $log->datos_nuevos['email'] ?? null);

        // La contraseña no debe aparecer en datos_nuevos ni en el payload serializado
        $this->assertArrayNotHasKey('password', (array) $log->datos_nuevos);
        $this->assertStringNotContainsString($plainPassword, json_encode($log->datos_nuevos) ?: '');
        $this->assertStringNotContainsString($plainPassword, json_encode($log->toArray()) ?: '');
    }
}
