<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_puede_ver_cualquier_usuario(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $vendedor));
    }

    public function test_vendedor_no_puede_ver_usuarios(): void
    {
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($vendedor)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($vendedor)->allows('view', $admin));
    }

    public function test_admin_no_puede_desactivar_su_cuenta(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($admin)->allows('desactivar', $admin));
    }

    public function test_admin_no_puede_eliminar_su_cuenta(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $admin));
    }

    public function test_admin_no_puede_eliminar_ultimo_admin(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        // Con un solo administrador en el sistema, no se puede eliminar
        $this->assertEquals(1, User::role('Admin')->count());
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $admin));

        // Incluso otro usuario con permiso 'eliminar usuarios' no puede eliminar al único admin
        $otroUsuario = User::factory()->create();
        $otroUsuario->givePermissionTo('eliminar usuarios');
        $this->assertFalse(Gate::forUser($otroUsuario)->allows('delete', $admin));

        // Si se crea un segundo administrador, ahora sí se puede eliminar al segundo admin
        $segundoAdmin = User::factory()->create();
        $segundoAdmin->assignRole('Admin');
        $this->assertEquals(2, User::role('Admin')->count());

        $this->assertTrue(Gate::forUser($admin)->allows('delete', $segundoAdmin));
    }

    public function test_admin_puede_desactivar_otro_usuario(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $this->assertTrue(Gate::forUser($admin)->allows('desactivar', $vendedor));
    }
}
