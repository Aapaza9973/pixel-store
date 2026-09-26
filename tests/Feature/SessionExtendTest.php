<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SessionExtendTest extends TestCase
{
    // El esquema del proyecto se administra con database/schema/pgsql-schema.sql
    // (no hay migraciones), por eso usamos transacciones en vez de RefreshDatabase.
    use DatabaseTransactions;

    public function test_authenticated_user_can_extend_session(): void
    {
        // El factory no define 'activo'; el default de la BD es true pero
        // Eloquent no lo recibe de vuelta, así que lo pasamos explícitamente.
        $user = User::factory()->create(['activo' => true]);

        $response = $this
            ->actingAs($user)
            ->postJson('/session/extend');

        $response
            ->assertOk()
            ->assertJson([
                'status'   => 'extended',
                'lifetime' => (int) config('session.lifetime'),
            ])
            ->assertJsonStructure(['status', 'lifetime', 'expires_at', 'csrf_token']);
    }

    public function test_extend_response_returns_new_expiry_and_csrf_token(): void
    {
        $user = User::factory()->create(['activo' => true]);
        $lifetime = (int) config('session.lifetime');

        $response = $this->actingAs($user)->postJson('/session/extend');

        $response->assertOk();

        // El token CSRF debe seguir siendo utilizable: si el backend rotara el
        // token sin devolverlo, el siguiente POST de la página fallaría con 419.
        $this->assertNotEmpty($response->json('csrf_token'));

        // La nueva expiración debe estar por delante del momento actual, dentro
        // del margen del lifetime configurado.
        $this->assertTrue(
            Carbon::parse($response->json('expires_at'))
                ->greaterThan(now()->addMinutes($lifetime)->subMinute())
        );
    }

    public function test_guest_cannot_extend_session(): void
    {
        $response = $this->postJson('/session/extend');

        $response->assertUnauthorized();
    }

    public function test_inactive_user_cannot_extend_session(): void
    {
        $user = User::factory()->create(['activo' => false]);

        $response = $this
            ->actingAs($user)
            ->postJson('/session/extend');

        $response->assertRedirect(route('login'));
    }

    public function test_extend_requires_post(): void
    {
        $user = User::factory()->create(['activo' => true]);

        $this->actingAs($user)
            ->get('/session/extend')
            ->assertMethodNotAllowed();
    }
}
