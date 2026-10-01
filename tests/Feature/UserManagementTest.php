<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LogAuditoria;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Flujo completo de administración de usuarios (HU-1.1).
 *
 * Cubre los 7 criterios de aceptación de la historia:
 * - Creación de usuarios con uno o más roles
 * - Validación de email duplicado
 * - Listado, edición y desactivación
 * - Protección de la cuenta propia
 * - Control de acceso por rol y por permiso
 * - Auditoría de autenticación
 */
class UserManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Carga base válida para el formulario de creación de usuarios.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function datosValidos(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Carlos Gomez',
            'email' => 'carlos.gomez@pixelstore.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'telefono' => '70012345',
            'nit_ci' => '1234567',
            'roles' => ['Vendedor'],
            'activo' => true,
        ], $overrides);
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

    public function test_admin_puede_crear_usuario_con_roles(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.usuarios.store'), $this->datosValidos());

        $response->assertRedirect(route('admin.usuarios.index'));
        $response->assertSessionHas('success', 'Usuario creado exitosamente.');

        $this->assertDatabaseHas('users', ['email' => 'carlos.gomez@pixelstore.com']);

        $nuevo = User::where('email', 'carlos.gomez@pixelstore.com')->firstOrFail();

        $this->assertTrue($nuevo->hasRole('Vendedor'));
        $this->assertTrue($nuevo->estaActivo());
        $this->assertTrue(Hash::check('Password123', $nuevo->password));
    }

    public function test_email_duplicado_devuelve_error(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), $this->datosValidos([
                'email' => $admin->email,
            ]));

        $response->assertSessionHasErrors(['email' => 'El email ya está registrado.']);

        // Solo existe el usuario original: no se creó una copia.
        $this->assertSame(1, User::where('email', $admin->email)->count());
    }

    public function test_admin_puede_ver_listado(): void
    {
        $vendedor = $this->vendedor();

        $response = $this->actingAs($this->admin())->get(route('admin.usuarios.index'));

        $response->assertOk();
        $response->assertSee('Gestión del personal con acceso al sistema');
        $response->assertSee($vendedor->name);
        $response->assertSee($vendedor->email);
        $response->assertSee('Vendedor');
    }

    public function test_admin_puede_editar_usuario(): void
    {
        $vendedor = $this->vendedor();

        $vista = $this->actingAs($this->admin())->get(route('admin.usuarios.edit', $vendedor));
        $vista->assertOk();

        $response = $this->actingAs($this->admin())->put(route('admin.usuarios.update', $vendedor), [
            'name' => 'Laura Vendedora Editada',
            'email' => $vendedor->email,
            'telefono' => '70099999',
            'nit_ci' => '7654321',
            'roles' => ['Cajero'],
            'activo' => true,
        ]);

        $response->assertRedirect(route('admin.usuarios.index'));
        $response->assertSessionHas('success', 'Usuario actualizado exitosamente.');

        $vendedor->refresh();

        $this->assertSame('Laura Vendedora Editada', $vendedor->name);
        $this->assertSame('70099999', $vendedor->telefono);
        $this->assertTrue($vendedor->hasRole('Cajero'));
        $this->assertFalse($vendedor->hasRole('Vendedor'));
    }

    public function test_admin_puede_desactivar_usuario(): void
    {
        $vendedor = $this->vendedor();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.usuarios.desactivar', $vendedor));

        $response->assertRedirect(route('admin.usuarios.index'));
        $response->assertSessionHas('success');

        $this->assertFalse($vendedor->refresh()->estaActivo());
        $this->assertDatabaseHas('users', [
            'id' => $vendedor->id,
            'activo' => false,
        ]);
    }

    public function test_admin_no_puede_desactivar_su_cuenta(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->post(route('admin.usuarios.desactivar', $admin));

        $response->assertForbidden();

        $this->assertTrue($admin->refresh()->estaActivo());
    }

    public function test_vendedor_no_puede_crear_usuario(): void
    {
        $vendedor = $this->vendedor();

        $vista = $this->actingAs($vendedor)->get(route('admin.usuarios.create'));
        $vista->assertForbidden();

        $response = $this->actingAs($vendedor)
            ->post(route('admin.usuarios.store'), $this->datosValidos());

        $response->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'carlos.gomez@pixelstore.com']);
    }

    public function test_usuario_sin_rol_no_accede_al_panel(): void
    {
        $sinRol = User::factory()->create();

        $this->assertSame(0, $sinRol->roles()->count());

        $response = $this->actingAs($sinRol)->get(route('admin.usuarios.index'));

        $response->assertForbidden();

        $this->assertDatabaseHas('logs_auditoria', [
            'user_id' => $sinRol->id,
            'accion' => 'acceso_denegado_sin_rol',
        ]);
    }

    public function test_login_con_password_incorrecta_registra_auditoria(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-incorrecta',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');

        $log = LogAuditoria::where('accion', 'login_fallido')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->email, $log->datos_nuevos['email'] ?? null);
        $this->assertArrayNotHasKey('password', (array) $log->datos_nuevos);
    }

    public function test_login_exitoso_redirige_al_dashboard(): void
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
    }

    public function test_selector_de_roles_muestra_las_5_opciones(): void
    {
        $rolesEsperados = ['Admin', 'Cajero', 'Cliente', 'Inventario', 'Vendedor'];

        $this->assertSame($rolesEsperados, Role::orderBy('name')->pluck('name')->all());

        $response = $this->actingAs($this->admin())->get(route('admin.usuarios.create'));

        $response->assertOk();
        $this->assertSame(5, substr_count($response->getContent(), 'name="roles[]"'));

        foreach ($rolesEsperados as $nombreRol) {
            $response->assertSee($nombreRol);
        }
    }

    public function test_usuario_puede_tener_multiples_roles(): void
    {
        $response = $this->actingAs($this->admin())->post(
            route('admin.usuarios.store'),
            $this->datosValidos(['roles' => ['Vendedor', 'Cajero']])
        );

        $response->assertRedirect(route('admin.usuarios.index'));

        $nuevo = User::where('email', 'carlos.gomez@pixelstore.com')->firstOrFail();

        $this->assertSame(2, $nuevo->roles()->count());
        $this->assertTrue($nuevo->hasRole('Vendedor'));
        $this->assertTrue($nuevo->hasRole('Cajero'));
        $this->assertSame(['Cajero', 'Vendedor'], $nuevo->getRoleNames()->sort()->values()->all());
    }
}
