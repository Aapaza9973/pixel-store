<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LogAuditoria;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Auditoría de cambios CRUD sobre usuarios (tarea 1.1.15 — UserObserver).
 *
 * Verifica que created/updated/deleted queden en `logs_auditoria`
 * y que NUNCA se expongan password ni remember_token.
 */
class UserObserverTest extends TestCase
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
     * Último registro de auditoría de una acción sobre un usuario.
     */
    private function ultimoLog(string $accion, int $modeloId): ?LogAuditoria
    {
        return LogAuditoria::query()
            ->where('accion', $accion)
            ->where('modelo', User::class)
            ->where('modelo_id', $modeloId)
            ->latest('id')
            ->first();
    }

    /**
     * Carga del formulario de edición con los datos actuales del usuario.
     *
     * @param  array<int, string>  $roles
     * @return array<string, mixed>
     */
    private function datosActualizacion(User $user, array $roles): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'nit_ci' => $user->nit_ci,
            'activo' => $user->estaActivo(),
            'roles' => $roles,
        ];
    }

    public function test_crear_usuario_registra_en_auditoria(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $nuevo = User::create([
            'name' => 'Usuario Observado',
            'email' => 'usuario.observado@pixelstore.com',
            'password' => 'Password123',
            'activo' => true,
        ]);

        $this->assertDatabaseHas('logs_auditoria', [
            'accion' => 'crear_user',
            'user_id' => $admin->id,
            'modelo' => User::class,
            'modelo_id' => $nuevo->id,
        ]);

        $log = $this->ultimoLog('crear_user', $nuevo->id);

        $this->assertNotNull($log);
        // El user_id es el admin que creó, NO el usuario recién creado.
        $this->assertSame($admin->id, (int) $log->user_id);
        $this->assertNotSame($nuevo->id, (int) $log->user_id);

        $this->assertSame('usuario.observado@pixelstore.com', $log->datos_nuevos['email'] ?? null);
        $this->assertSame('Usuario Observado', $log->datos_nuevos['name'] ?? null);
        $this->assertArrayNotHasKey('password', $log->datos_nuevos);
        $this->assertArrayNotHasKey('remember_token', $log->datos_nuevos);

        $this->assertNull($log->datos_anteriores);
        $this->assertNotNull($log->ip);
        $this->assertNotNull($log->user_agent);
    }

    public function test_editar_usuario_registra_diff_en_auditoria(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
        $nombreAnterior = $vendedor->name;

        $vendedor->update([
            'name' => 'Laura Editada Por Observer',
            'telefono' => '711223344',
        ]);

        $this->assertDatabaseHas('logs_auditoria', [
            'accion' => 'editar_user',
            'user_id' => $admin->id,
            'modelo' => User::class,
            'modelo_id' => $vendedor->id,
        ]);

        $log = $this->ultimoLog('editar_user', $vendedor->id);

        $this->assertNotNull($log);
        $this->assertSame($nombreAnterior, $log->datos_anteriores['name'] ?? null);
        $this->assertSame('Laura Editada Por Observer', $log->datos_nuevos['name'] ?? null);
        $this->assertSame('711223344', $log->datos_nuevos['telefono'] ?? null);

        // datos_anteriores contiene SOLO las claves que cambiaron.
        $this->assertArrayHasKey('name', $log->datos_anteriores);
        $this->assertArrayHasKey('telefono', $log->datos_anteriores);
        $this->assertArrayNotHasKey('email', $log->datos_anteriores);
        $this->assertArrayNotHasKey('email', $log->datos_nuevos);
        $this->assertArrayNotHasKey('id', $log->datos_anteriores);
        $this->assertEqualsCanonicalizing(
            array_keys($log->datos_anteriores),
            array_keys($log->datos_nuevos)
        );
    }

    public function test_eliminar_usuario_registra_en_auditoria(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $victima = User::create([
            'name' => 'Usuario Para Eliminar',
            'email' => 'usuario.eliminar@pixelstore.com',
            'password' => 'Password123',
        ]);
        $victimaId = $victima->id;

        $victima->delete();

        $this->assertDatabaseMissing('users', ['id' => $victimaId]);

        $this->assertDatabaseHas('logs_auditoria', [
            'accion' => 'eliminar_user',
            'user_id' => $admin->id,
            'modelo' => User::class,
            'modelo_id' => $victimaId,
        ]);

        $log = $this->ultimoLog('eliminar_user', $victimaId);

        $this->assertNotNull($log);
        $this->assertSame('usuario.eliminar@pixelstore.com', $log->datos_anteriores['email'] ?? null);
        $this->assertArrayNotHasKey('password', $log->datos_anteriores);
        $this->assertArrayNotHasKey('remember_token', $log->datos_anteriores);
        $this->assertNull($log->datos_nuevos);
    }

    public function test_cambio_de_password_no_se_registra_en_auditoria(): void
    {
        $this->actingAs($this->admin());

        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $vendedor->update(['password' => 'ClaveNuevaSegura123']);

        $this->assertDatabaseMissing('logs_auditoria', [
            'accion' => 'editar_user',
            'modelo' => User::class,
            'modelo_id' => $vendedor->id,
        ]);

        $this->assertTrue(Hash::check('ClaveNuevaSegura123', $vendedor->fresh()->password));
    }

    public function test_sin_cambios_no_genera_registro(): void
    {
        $this->actingAs($this->admin());

        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        // 1) Guardar sin atributos modificados.
        $vendedor->save();

        // 2) Actualización cuyo único cambio es un atributo excluido (remember_token).
        $vendedor->setRememberToken(Str::random(60));
        $vendedor->save();

        // 3) Reasignar el mismo valor no debe generar diff.
        $vendedor->update(['name' => $vendedor->name]);

        $this->assertDatabaseMissing('logs_auditoria', [
            'accion' => 'editar_user',
            'modelo' => User::class,
            'modelo_id' => $vendedor->id,
        ]);
    }

    public function test_password_no_aparece_en_el_diff_de_auditoria(): void
    {
        $this->actingAs($this->admin());

        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $vendedor->update([
            'name' => 'Laura Con Password Nueva',
            'password' => 'PasswordNueva123',
        ]);

        $log = $this->ultimoLog('editar_user', $vendedor->id);

        $this->assertNotNull($log);
        $this->assertSame('Laura Con Password Nueva', $log->datos_nuevos['name'] ?? null);
        $this->assertArrayNotHasKey('password', $log->datos_nuevos);
        $this->assertArrayNotHasKey('password', $log->datos_anteriores);

        $serializado = json_encode($log->toArray()) ?: '';
        $hash = (string) $vendedor->fresh()->password;

        $this->assertStringNotContainsString($hash, $serializado);
        $this->assertStringNotContainsString('PasswordNueva123', $serializado);
    }

    public function test_sin_sesion_no_registra_en_auditoria(): void
    {
        $this->assertGuest();

        $nuevo = User::create([
            'name' => 'Usuario Sin Sesion',
            'email' => 'usuario.sinsesion@pixelstore.com',
            'password' => 'Password123',
        ]);

        $this->assertDatabaseMissing('logs_auditoria', [
            'accion' => 'crear_user',
            'modelo' => User::class,
            'modelo_id' => $nuevo->id,
        ]);
    }

    public function test_cambio_de_roles_registra_en_auditoria(): void
    {
        $admin = $this->admin();
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $response = $this->actingAs($admin)->put(
            route('admin.usuarios.update', $vendedor),
            $this->datosActualizacion($vendedor, ['Vendedor', 'Cajero'])
        );

        $response->assertRedirect(route('admin.usuarios.index'));
        $response->assertSessionHas('success');

        $this->assertTrue($vendedor->fresh()->hasRole('Cajero'));

        $this->assertDatabaseHas('logs_auditoria', [
            'accion' => 'cambiar_roles_user',
            'user_id' => $admin->id,
            'modelo' => User::class,
            'modelo_id' => $vendedor->id,
        ]);

        $log = $this->ultimoLog('cambiar_roles_user', $vendedor->id);

        $this->assertNotNull($log);
        $this->assertSame($admin->id, (int) $log->user_id);
        $this->assertSame(['Vendedor'], $log->datos_anteriores['roles'] ?? null);
        $this->assertSame(['Cajero', 'Vendedor'], $log->datos_nuevos['roles'] ?? null);
        $this->assertArrayNotHasKey('password', $log->datos_anteriores);
        $this->assertArrayNotHasKey('password', $log->datos_nuevos);
    }

    public function test_sin_cambio_de_roles_no_genera_registro_de_roles(): void
    {
        $admin = $this->admin();
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $response = $this->actingAs($admin)->put(
            route('admin.usuarios.update', $vendedor),
            $this->datosActualizacion($vendedor, ['Vendedor'])
        );

        $response->assertRedirect(route('admin.usuarios.index'));

        $this->assertTrue($vendedor->fresh()->hasRole('Vendedor'));

        $this->assertDatabaseMissing('logs_auditoria', [
            'accion' => 'cambiar_roles_user',
            'modelo' => User::class,
            'modelo_id' => $vendedor->id,
        ]);
    }
}
