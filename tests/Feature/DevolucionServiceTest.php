<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DomainException;
use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Devolucion;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\DevolucionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class DevolucionServiceTest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    private Producto $producto;

    private DevolucionService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
        $this->servicio = app(DevolucionService::class);

        $categoria = Categoria::create(['nombre' => 'Categoría Servicio Devolución', 'tipo' => 'Componente']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Teclado Devolución',
            'precio_unitario' => 250,
            'stock' => 10,
            'umbral_alerta' => 1,
        ]);
    }

    /**
     * Venta pagada con una sola línea del producto del test.
     */
    private function crearVenta(int $cantidad): Venta
    {
        $venta = Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'user_id' => $this->vendedor->id,
            'total' => 250 * $cantidad,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => $cantidad,
            'precio_unitario' => 250,
        ]);

        return $venta;
    }

    public function test_registra_devolucion_total_con_cabecera_y_linea(): void
    {
        $venta = $this->crearVenta(2);
        $detalle = $venta->detalles()->firstOrFail();
        $operacionId = (string) Str::uuid();

        $devolucion = $this->servicio->registrar(
            $venta->id,
            [['venta_detalle_id' => $detalle->id, 'cantidad' => 2]],
            'Producto con falla de fábrica',
            $operacionId,
            $this->vendedor->id
        );

        $this->assertSame($venta->id, (int) $devolucion->venta_id);
        $this->assertSame($this->vendedor->id, (int) $devolucion->user_id);
        $this->assertSame($operacionId, $devolucion->operacion_id);
        $this->assertSame('procesada', $devolucion->estado);
        $this->assertSame('500.00', $devolucion->total_devuelto);
        $this->assertCount(1, $devolucion->detalles);

        $linea = $devolucion->detalles->first();

        $this->assertSame($detalle->id, $linea->venta_detalle_id);
        $this->assertSame($this->producto->id, (int) $linea->producto_id);
        $this->assertSame(2, $linea->cantidad);
        $this->assertSame('500.00', $linea->monto);
        $this->assertSame('Pendiente', $linea->estado);

        // Desde 5.14.6 la devolución reingresa el stock: 10 + 2 devueltas = 12.
        $this->assertSame(12, (int) $this->producto->fresh()->stock);
    }

    public function test_devolucion_parcial_acumula_hasta_lo_vendido(): void
    {
        $venta = $this->crearVenta(5);
        $detalle = $venta->detalles()->firstOrFail();
        $items = fn (int $cantidad): array => [['venta_detalle_id' => $detalle->id, 'cantidad' => $cantidad]];

        $primera = $this->servicio->registrar(
            $venta->id, $items(2), 'Primera devolución', (string) Str::uuid(), $this->vendedor->id
        );

        $segunda = $this->servicio->registrar(
            $venta->id, $items(2), 'Segunda devolución', (string) Str::uuid(), $this->vendedor->id
        );

        $this->assertSame('500.00', $primera->total_devuelto);
        $this->assertSame('500.00', $segunda->total_devuelto);
        $this->assertSame(2, Devolucion::where('venta_id', $venta->id)->count());

        // Queda 1 unidad: pedir 2 es un error de negocio, no un 500.
        try {
            $this->servicio->registrar(
                $venta->id, $items(2), 'Tercera devolución', (string) Str::uuid(), $this->vendedor->id
            );

            $this->fail('Se esperaba DomainException: solo queda 1 unidad por devolver.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('Solo queda 1 unidad por devolver', $e->getMessage());
        }

        $this->assertSame(2, Devolucion::where('venta_id', $venta->id)->count());
    }

    public function test_linea_de_otra_venta_es_rechazada(): void
    {
        $venta = $this->crearVenta(1);
        $otraVenta = $this->crearVenta(1);
        $detalleAjeno = $otraVenta->detalles()->firstOrFail();

        try {
            $this->servicio->registrar(
                $venta->id,
                [['venta_detalle_id' => $detalleAjeno->id, 'cantidad' => 1]],
                'Línea ajena',
                (string) Str::uuid(),
                $this->vendedor->id
            );

            $this->fail('Se esperaba DomainException: la línea es de otra venta.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('no pertenece a la venta indicada', $e->getMessage());
        }

        $this->assertSame(0, Devolucion::where('venta_id', $venta->id)->count());
    }

    public function test_cantidad_cero_o_negativa_es_rechazada(): void
    {
        $venta = $this->crearVenta(3);
        $detalle = $venta->detalles()->firstOrFail();

        foreach ([0, -2] as $cantidad) {
            try {
                $this->servicio->registrar(
                    $venta->id,
                    [['venta_detalle_id' => $detalle->id, 'cantidad' => $cantidad]],
                    'Cantidad inválida',
                    (string) Str::uuid(),
                    $this->vendedor->id
                );

                $this->fail("Se esperaba DomainException para cantidad {$cantidad}.");
            } catch (DomainException $e) {
                $this->assertStringContainsString('mayor que cero', $e->getMessage());
            }
        }

        $this->assertSame(0, Devolucion::where('venta_id', $venta->id)->count());
    }

    public function test_mismo_operacion_id_no_duplica_la_devolucion(): void
    {
        $venta = $this->crearVenta(2);
        $detalle = $venta->detalles()->firstOrFail();
        $operacionId = (string) Str::uuid();
        $items = [['venta_detalle_id' => $detalle->id, 'cantidad' => 2]];

        $primera = $this->servicio->registrar(
            $venta->id, $items, 'Falla', $operacionId, $this->vendedor->id
        );

        $segunda = $this->servicio->registrar(
            $venta->id, $items, 'Falla', $operacionId, $this->vendedor->id
        );

        $this->assertSame($primera->id, $segunda->id);
        $this->assertSame(1, Devolucion::where('operacion_id', $operacionId)->count());
        $this->assertCount(1, $segunda->detalles);
    }
}
