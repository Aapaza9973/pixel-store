<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use DatabaseTransactions; // Solo transacciones, no refresca la BD

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_admin_puede_acceder_al_dashboard(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->first();
        $this->assertNotNull($admin, 'Usuario admin no encontrado');
        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_vendedor_puede_acceder_al_dashboard(): void
    {
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->first();
        $this->assertNotNull($vendedor, 'Usuario vendedor no encontrado');
        $response = $this->actingAs($vendedor)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_usuario_desactivado_no_puede_acceder(): void
    {
        $user = User::where('email', 'vendedor@pixelstore.com')->first();
        $user->update(['activo' => false]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('login'));
    }

    public function test_admin_tiene_rol_asignado(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->first();
        $this->assertTrue($admin->hasRole('Admin'));
    }

    public function test_vendedor_no_tiene_rol_admin(): void
    {
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->first();
        $this->assertFalse($vendedor->hasRole('Admin'));
    }
}
