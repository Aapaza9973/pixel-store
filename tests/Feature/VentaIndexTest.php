<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VentaIndexTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $this->cliente = Cliente::create([
            'nombre' => 'Cliente Historial',
            'nit_ci' => '9988776',
        ]);
    }

    private function crearVenta(string $origen, string $estado, float $total, ?int $userId): Venta
    {
        return Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'fecha' => now(),
            'user_id' => $userId,
            'cliente_id' => $this->cliente->id,
            'total' => $total,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => $estado,
            'origen' => $origen,
        ]);
    }

    public function test_admin_puede_ver_el_historial_de_ventas(): void
    {
        $pos = $this->crearVenta('pos', 'Pagado', 1200, $this->admin->id);

        $response = $this->actingAs($this->admin)->get(route('admin.ventas.index'));

        $response->assertOk();
        $response->assertSee('Historial de Ventas');
        $response->assertSee('#'.str_pad((string) $pos->id, 6, '0', STR_PAD_LEFT));
        $response->assertSee('Cliente Historial');
        $response->assertSee('POS');
        $response->assertSee('Ver detalle');
    }

    public function test_filtro_por_origen_funciona(): void
    {
        $pos = $this->crearVenta('pos', 'Pagado', 800, $this->admin->id);
        $web = $this->crearVenta('web', 'Pendiente', 450, null);

        $response = $this->actingAs($this->admin)->get(route('admin.ventas.index', ['origen' => 'web']));

        $response->assertOk();
        $response->assertSee('#'.str_pad((string) $web->id, 6, '0', STR_PAD_LEFT));
        $response->assertDontSee('#'.str_pad((string) $pos->id, 6, '0', STR_PAD_LEFT));
        $response->assertSee('Web');
    }

    public function test_vendedor_sin_permiso_no_ve_el_historial(): void
    {
        // Rol propio sin "ver ventas": no se muta el rol global porque eso deja
        // la caché de Spatie envenenada para los tests siguientes del proceso.
        $rol = Role::create(['name' => 'Vendedor Mostrador', 'guard_name' => 'web']);
        $rol->syncPermissions(['crear ventas']);

        $vendedor = User::create([
            'name' => 'Vendedor Sin Historial',
            'email' => 'vendedor.mostrador@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'activo' => true,
        ]);
        $vendedor->assignRole($rol);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->actingAs($vendedor)->get(route('admin.ventas.index'));

        $response->assertForbidden();
    }
}
