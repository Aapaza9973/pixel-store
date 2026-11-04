<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CierreCaja;
use App\Models\Pago;
use App\Models\User;
use App\Models\Venta;
use App\Services\CierreCajaService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * HU-6.1 (6.1.6-6.1.10): cierre de caja, autorización y reporte diario.
 */
class CierreCajaControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $cajero;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $this->cajero = User::where('email', 'cajero@pixelstore.com')->firstOrFail();
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
    }

    private function crearVenta(float $total, string $metodo = 'Efectivo', ?User $vendedor = null): Venta
    {
        $venta = Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'user_id' => ($vendedor ?? $this->cajero)->id,
            'total' => $total,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
        ]);

        Pago::create([
            'venta_id' => $venta->id,
            'monto' => $total,
            'metodo' => $metodo,
            'estado' => 'Completado',
        ]);

        return $venta;
    }

    public function test_index_lista_los_cierres_del_dia(): void
    {
        $this->crearVenta(600);

        app(CierreCajaService::class)->cerrar(
            $this->cajero->id,
            now()->toDateString(),
            'tarde',
            ['efectivo' => 600]
        );

        $response = $this->actingAs($this->cajero)->get(route('admin.caja.index'));

        $response->assertOk();
        $response->assertSee('Cierre de caja');
        $response->assertSee('tarde');
        $response->assertSee($this->cajero->name);
        $response->assertSee('600.00');
    }

    public function test_crear_cierre_guarda_diferencia_y_redirect(): void
    {
        $this->crearVenta(600);

        $response = $this->actingAs($this->cajero)->post(route('admin.caja.store'), [
            'fecha' => now()->toDateString(),
            'turno' => 'mañana',
            'montos' => ['efectivo' => 590],
        ]);

        $response->assertRedirect(route('admin.caja.index'));
        $response->assertSessionHas('success');

        $cierre = CierreCaja::where('user_id', $this->cajero->id)->firstOrFail();
        $this->assertSame(600.0, (float) $cierre->efectivo_esperado);
        $this->assertSame(590.0, (float) $cierre->efectivo_real);
        $this->assertSame(-10.0, (float) $cierre->diferencia);
        $this->assertSame(5, $cierre->pagos()->count());
    }

    public function test_cajero_no_puede_cerrar_turno_de_otro(): void
    {
        $this->crearVenta(400);

        $response = $this->actingAs($this->cajero)->post(route('admin.caja.store'), [
            'fecha' => now()->toDateString(),
            'turno' => 'tarde',
            'user_id' => $this->vendedor->id,
            'montos' => ['efectivo' => 0],
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('cierres_caja', [
            'user_id' => $this->vendedor->id,
            'turno' => 'tarde',
        ]);
    }

    public function test_vendedor_no_puede_acceder_a_caja(): void
    {
        // El Vendedor tiene 'ver caja'/'crear caja' en el RoleSeeder: la caja se
        // gatea por rol, no por permiso.
        $this->actingAs($this->vendedor)
            ->get(route('admin.caja.index'))
            ->assertForbidden();

        $this->actingAs($this->vendedor)
            ->post(route('admin.caja.store'), [
                'fecha' => now()->toDateString(),
                'turno' => 'noche',
                'montos' => ['efectivo' => 0],
            ])
            ->assertForbidden();
    }

    public function test_admin_puede_ver_el_reporte_diario(): void
    {
        $this->crearVenta(250, 'Tarjeta');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.caja.reporte', ['fecha' => now()->toDateString()]));

        $response->assertOk();
        $response->assertSee('Reporte diario');
        $response->assertSee('Tarjeta');
        $response->assertSee($this->cajero->name);
        $response->assertSee('250.00');
    }

    public function test_cerrar_turno_duplicado_redirige_con_error(): void
    {
        $this->crearVenta(300);

        $datos = [
            'fecha' => now()->toDateString(),
            'turno' => 'noche',
            'montos' => ['efectivo' => 300],
        ];

        $this->actingAs($this->cajero)->post(route('admin.caja.store'), $datos)
            ->assertRedirect(route('admin.caja.index'));

        $this->actingAs($this->cajero)->post(route('admin.caja.store'), $datos)
            ->assertSessionHas('error');

        $this->assertSame(1, CierreCaja::where('user_id', $this->cajero->id)->count());
    }
}
