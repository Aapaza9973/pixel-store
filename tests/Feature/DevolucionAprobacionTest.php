<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\DevolucionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class DevolucionAprobacionTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
    }

    public function test_una_solicitud_no_mueve_stock(): void
    {
        [$venta, $detalle, $producto] = $this->ventaConDetalle();

        $devolucion = $this->service()->solicitar(
            $venta->id,
            [['venta_detalle_id' => $detalle->id, 'cantidad' => 1]],
            'No le gustó',
            (string) Str::uuid(),
            $this->vendedor->id,
        );

        $this->assertSame('pendiente', $devolucion->estado);
        $this->assertSame(5, $producto->fresh()->stock);
    }

    public function test_endpoint_de_solicitud_responde_json_y_no_mueve_stock(): void
    {
        [$venta, $detalle, $producto] = $this->ventaConDetalle();

        $this->actingAs($this->vendedor)
            ->postJson(route('admin.devoluciones.solicitar', $venta), [
                'operacion_id' => (string) Str::uuid(),
                'motivo' => 'Solicitud desde el POS',
                'items' => [['venta_detalle_id' => $detalle->id, 'cantidad' => 1]],
            ])
            ->assertCreated()
            ->assertJsonPath('devolucion.estado', 'pendiente');

        $this->assertSame(5, $producto->fresh()->stock);
    }

    public function test_admin_aprueba_y_reingresa_stock(): void
    {
        [$venta, $detalle, $producto] = $this->ventaConDetalle();

        $devolucion = $this->service()->solicitar(
            $venta->id,
            [['venta_detalle_id' => $detalle->id, 'cantidad' => 1]],
            'Defectuoso',
            (string) Str::uuid(),
            $this->vendedor->id,
        );

        $this->actingAs($this->admin)
            ->post(route('admin.devoluciones.aprobar', $devolucion))
            ->assertSessionHas('success');

        $this->assertSame('procesada', $devolucion->fresh()->estado);
        $this->assertSame(6, $producto->fresh()->stock);
        $this->assertSame($this->admin->id, (int) $devolucion->fresh()->aprobada_por);
    }

    public function test_admin_rechaza_con_motivo(): void
    {
        [$venta, $detalle, $producto] = $this->ventaConDetalle();

        $devolucion = $this->service()->solicitar(
            $venta->id,
            [['venta_detalle_id' => $detalle->id, 'cantidad' => 1]],
            'Cambio de opinión',
            (string) Str::uuid(),
            $this->vendedor->id,
        );

        $this->actingAs($this->admin)
            ->post(route('admin.devoluciones.rechazar', $devolucion), ['motivo' => 'Fuera de plazo'])
            ->assertSessionHas('success');

        $this->assertSame('rechazada', $devolucion->fresh()->estado);
        $this->assertSame('Fuera de plazo', $devolucion->fresh()->motivo_rechazo);
        $this->assertSame(5, $producto->fresh()->stock);
    }

    public function test_vendedor_no_puede_aprobar(): void
    {
        [$venta, $detalle] = $this->ventaConDetalle();

        $devolucion = $this->service()->solicitar(
            $venta->id,
            [['venta_detalle_id' => $detalle->id, 'cantidad' => 1]],
            'Talla incorrecta',
            (string) Str::uuid(),
            $this->vendedor->id,
        );

        $this->actingAs($this->vendedor)
            ->post(route('admin.devoluciones.aprobar', $devolucion))
            ->assertForbidden();

        $this->assertSame('pendiente', $devolucion->fresh()->estado);
    }

    public function test_admin_ve_la_bandeja_de_devoluciones(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.devoluciones.index'))
            ->assertOk()
            ->assertSee('Devoluciones');
    }

    /**
     * @return array{0: Venta, 1: DetalleVenta, 2: Producto}
     */
    private function ventaConDetalle(): array
    {
        static $secuencia = 0;
        $secuencia++;

        $categoria = Categoria::firstOrCreate(['nombre' => 'Devoluciones Test'], ['tipo' => 'Componente']);

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto devolución '.$secuencia,
            'slug' => 'producto-devolucion-'.$secuencia,
            'precio_unitario' => 100,
            'stock' => 5,
            'umbral_alerta' => 1,
        ]);

        $venta = Venta::create([
            'fecha' => now(),
            'total' => 100,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'puntos_canjeados' => 0,
            'estado' => 'Pagado',
            'origen' => 'pos',
        ]);

        $detalle = DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 100,
        ]);

        return [$venta, $detalle, $producto];
    }

    private function service(): DevolucionService
    {
        return app(DevolucionService::class);
    }
}
