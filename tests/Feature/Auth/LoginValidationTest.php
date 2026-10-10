<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LoginValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_view_contiene_directivas_alpine(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('x-data="loginForm', false);
        $response->assertSee('x-model="email"', false);
    }

    public function test_login_email_vacio_devuelve_error_servidor(): void
    {
        $response = $this->post('/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_preserva_old_email_al_fallar(): void
    {
        $emailInvalido = 'esto-no-es-un-email';

        $response = $this->from('/login')->post('/login', [
            'email' => $emailInvalido,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertRedirect('/login');

        $this->get('/login')->assertSee($emailInvalido, false);
    }
}
