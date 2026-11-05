<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\NumeroSerie;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cierre de Sprint 2: recorrido E2E del happy path sobre HTTP real.
 *
 * No reemplaza los tests por módulo: valida que las piezas encajen entre sí
 * (login → venta → comprobante → devolución → caja → reportes → PDF).
 */
class SprintE2ETest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    private User $admin;

    private Producto $serializado;

    private Producto $simple;

    private NumeroSerie $serie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría E2E Sprint 2', 'tipo' => 'Componente']);

        $this->serializado = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'SSD E2E Serializado',
            'precio_unitario' => 600,
            'costo' => 400,
            'stock' => 3,
            'umbral_alerta' => 1,
            'maneja_numero_serie' => true,
        ]);

        $this->simple = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Mouse E2E Simple',
            'precio_unitario' => 100,
            'costo' => 50,
            'stock' => 5,
            'umbral_alerta' => 1,
            'maneja_numero_serie' => false,
        ]);

        $this->serie = NumeroSerie::create([
            'producto_id' => $this->serializado->id,
            'numero_serie' => 'SN-E2E-'.Str::upper(Str::random(8)),
            'estado' => 'disponible',
            'fecha_ingreso' => now(),
        ]);
    }

    public function test_flujo_completo_venta_comprobante_devolucion_reporte(): void
    {
        // 1) Login como vendedor.
        $this->post(route('login'), [
            'email' => 'vendedor@pixelstore.com',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($this->vendedor);

        // 2) Venta: 2 productos (uno serializado) y 2 pagos, con 5 % de
        // descuento (el límite del Vendedor es 10 %).
        $operacion = (string) Str::uuid();

        $respuesta = $this->postJson(route('admin.ventas.store'), [
            'items' => [
                [
                    'producto_id' => $this->serializado->id,
                    'cantidad' => 1,
                    'numero_serie_id' => $this->serie->id,
                ],
                [
                    'producto_id' => $this->simple->id,
                    'cantidad' => 1,
                ],
            ],
            'pagos' => [
                ['metodo' => 'Efectivo', 'monto' => 400],
                ['metodo' => 'Tarjeta', 'monto' => 265, 'referencia' => 'POS-E2E-001'],
            ],
            'descuento_porcentaje' => 5,
            'operacion_id' => $operacion,
        ]);

        $respuesta->assertCreated()->assertJsonPath('venta.estado', 'Pagado');

        $venta = Venta::where('operacion_id', $operacion)->firstOrFail();

        // 3) Total con descuento y stock decrementado.
        $this->assertSame(665.0, (float) $venta->total);
        $this->assertSame(35.0, (float) $venta->descuento);
        $this->assertSame(2, (int) $this->serializado->fresh()->stock);
        $this->assertSame(4, (int) $this->simple->fresh()->stock);
        $this->assertSame(2, $venta->detalles()->count());

        // 4) Comprobante imprimible.
        $numero = '#'.str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT);

        $this->get(route('admin.ventas.comprobante', $venta))
            ->assertOk()
            ->assertSee($numero);

        // 5) Detalle de la venta.
        $this->get(route('admin.ventas.show', $venta))->assertOk();

        // 6) Devolución de la unidad serializada: venta parcial y serie liberada.
        $lineaSerie = $venta->detalles()->whereNotNull('numero_serie_id')->firstOrFail();

        $this->postJson(route('admin.ventas.devoluciones.store', $venta), [
            'operacion_id' => (string) Str::uuid(),
            'motivo' => 'Equipo con falla de fábrica',
            'items' => [
                ['venta_detalle_id' => $lineaSerie->id, 'cantidad' => 1],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('venta_estado', 'parcialmente_devuelta');

        $this->serie->refresh();
        $this->assertSame('disponible', $this->serie->estado);
        $this->assertNull($this->serie->venta_id);
        $this->assertSame(3, (int) $this->serializado->fresh()->stock);

        // 7) Cambio de turno: logout y login como admin.
        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();

        $this->post(route('login'), [
            'email' => 'admin@pixelstore.com',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($this->admin);

        $hoy = now()->toDateString();

        // 8) Reporte de caja del día.
        $this->get(route('admin.caja.reporte', ['fecha' => $hoy]))->assertOk();

        // 9) Reporte de ventas por período.
        $this->get(route('admin.reportes.periodo', [
            'desde' => $hoy,
            'hasta' => $hoy,
            'agrupacion' => 'dia',
        ]))->assertOk();

        // 10) Indicadores (utilidad, ranking, antigüedad).
        $this->get(route('admin.reportes.indicadores', [
            'desde' => $hoy,
            'hasta' => $hoy,
        ]))->assertOk();

        // 11) Exportación PDF.
        $this->get(route('admin.reportes.pdf', [
            'tipo' => 'vendedor',
            'desde' => $hoy,
            'hasta' => $hoy,
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // 12) Logout final: el panel vuelve a exigir sesión.
        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();

        $this->get(route('admin.ventas.create'))->assertRedirect(route('login'));
    }
}
