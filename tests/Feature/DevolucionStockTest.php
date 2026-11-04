<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\NumeroSerie;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\DevolucionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class DevolucionStockTest extends TestCase
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

        $categoria = Categoria::create(['nombre' => 'Categoría Devolución Stock', 'tipo' => 'Componente']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Mouse Devolución Stock',
            'precio_unitario' => 100,
            'stock' => 10,
            'umbral_alerta' => 1,
        ]);
    }

    private function crearVenta(int $cantidad, ?int $numeroSerieId = null): Venta
    {
        $venta = Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'user_id' => $this->vendedor->id,
            'total' => 100 * $cantidad,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
        ]);

        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'numero_serie_id' => $numeroSerieId,
            'cantidad' => $cantidad,
            'precio_unitario' => 100,
        ]);

        return $venta;
    }

    private function devolver(Venta $venta, int $cantidad): void
    {
        $detalle = $venta->detalles()->firstOrFail();

        $this->servicio->registrar(
            $venta->id,
            [['venta_detalle_id' => $detalle->id, 'cantidad' => $cantidad]],
            'Motivo de prueba',
            (string) Str::uuid(),
            $this->vendedor->id
        );
    }

    public function test_devolucion_reingresa_stock(): void
    {
        // Vende 2 con stock 10 → queda 8.
        $this->producto->update(['stock' => 8]);
        $venta = $this->crearVenta(2);

        $this->devolver($venta, 2);

        $this->assertSame(10, (int) $this->producto->fresh()->stock);
        $this->assertDatabaseHas('movimientos_stock', [
            'producto_id' => $this->producto->id,
            'tipo' => 'devolucion',
            'cantidad' => 2,
            'stock_resultante' => 10,
        ]);
    }

    public function test_devolucion_libera_el_numero_de_serie(): void
    {
        $serie = NumeroSerie::create([
            'producto_id' => $this->producto->id,
            'numero_serie' => 'SN-DEV-'.Str::random(6),
            'estado' => 'vendido',
            'venta_id' => null,
            'cliente_id' => null,
            'fecha_ingreso' => now(),
            'fecha_venta' => now(),
        ]);

        $venta = $this->crearVenta(1, $serie->id);
        $serie->update(['venta_id' => $venta->id]);

        $this->devolver($venta, 1);

        $serie->refresh();

        $this->assertSame('disponible', $serie->estado);
        $this->assertNull($serie->venta_id);
        $this->assertNull($serie->fecha_venta);
        $this->assertNull($serie->cliente_id);
    }

    public function test_devolucion_parcial_marca_la_venta_como_parcialmente_devuelta(): void
    {
        $venta = $this->crearVenta(5);

        $this->devolver($venta, 2);

        $this->assertSame('parcialmente_devuelta', $venta->fresh()->estado);
    }

    public function test_devolucion_total_marca_la_venta_como_totalmente_devuelta(): void
    {
        $venta = $this->crearVenta(2);

        $this->devolver($venta, 2);

        $this->assertSame('totalmente_devuelta', $venta->fresh()->estado);
    }

    public function test_segunda_devolucion_parcial_sobre_lo_restante_totaliza(): void
    {
        // Stock inicial 5, venta de 5 (crearVenta no descuenta stock), luego se devuelven
        // 2 + 3 = 5 unidades: el stock final es 5 + 5 = 10.
        $this->producto->update(['stock' => 5]);
        $venta = $this->crearVenta(5);

        $this->devolver($venta, 2);
        $this->assertSame('parcialmente_devuelta', $venta->fresh()->estado);

        $this->devolver($venta, 3);

        $this->assertSame('totalmente_devuelta', $venta->fresh()->estado);
        $this->assertSame(10, (int) $this->producto->fresh()->stock);
    }

    /**
     * Venta de N unidades, una línea por unidad, cada una con su propio SN.
     *
     * @return array{venta: Venta, series: array<int, NumeroSerie>, detalles: array<int, DetalleVenta>}
     */
    private function crearVentaConSeries(int $cantidad): array
    {
        $venta = Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'user_id' => $this->vendedor->id,
            'total' => 100 * $cantidad,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
        ]);

        $series = [];
        $detalles = [];

        for ($i = 1; $i <= $cantidad; $i++) {
            $serie = NumeroSerie::create([
                'producto_id' => $this->producto->id,
                'numero_serie' => 'SN-SERIE-'.$i.'-'.Str::random(6),
                'estado' => 'vendido',
                'venta_id' => $venta->id,
                'cliente_id' => null,
                'fecha_ingreso' => now()->subDays(5),
                'fecha_venta' => now(),
            ]);

            $detalles[] = DetalleVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $this->producto->id,
                'numero_serie_id' => $serie->id,
                'cantidad' => 1,
                'precio_unitario' => 100,
            ]);

            $series[] = $serie;
        }

        return ['venta' => $venta, 'series' => $series, 'detalles' => $detalles];
    }

    public function test_devolucion_parcial_con_serie_solo_libera_la_serie_devuelta(): void
    {
        $datos = $this->crearVentaConSeries(2);
        [$devuelta, $intacta] = $datos['series'];

        $this->servicio->registrar(
            $datos['venta']->id,
            [['venta_detalle_id' => $datos['detalles'][0]->id, 'cantidad' => 1]],
            'Falla de una unidad',
            (string) Str::uuid(),
            $this->vendedor->id
        );

        $devuelta->refresh();
        $intacta->refresh();

        $this->assertSame('disponible', $devuelta->estado);
        $this->assertNull($devuelta->venta_id);
        $this->assertNull($devuelta->fecha_venta);

        $this->assertSame('vendido', $intacta->estado);
        $this->assertSame($datos['venta']->id, (int) $intacta->venta_id);
        $this->assertNotNull($intacta->fecha_venta);
    }

    public function test_devolucion_total_con_series_libera_todas(): void
    {
        $datos = $this->crearVentaConSeries(2);

        $this->servicio->registrar(
            $datos['venta']->id,
            [
                ['venta_detalle_id' => $datos['detalles'][0]->id, 'cantidad' => 1],
                ['venta_detalle_id' => $datos['detalles'][1]->id, 'cantidad' => 1],
            ],
            'Devolución total',
            (string) Str::uuid(),
            $this->vendedor->id
        );

        foreach ($datos['series'] as $serie) {
            $serie->refresh();

            $this->assertSame('disponible', $serie->estado);
            $this->assertNull($serie->venta_id);
            $this->assertNull($serie->fecha_venta);
        }

        $this->assertSame('totalmente_devuelta', $datos['venta']->fresh()->estado);
    }

    public function test_devolucion_sin_serie_reingresa_stock_sin_tocar_numeros_serie(): void
    {
        $intacta = NumeroSerie::create([
            'producto_id' => $this->producto->id,
            'numero_serie' => 'SN-AJENA-'.Str::random(6),
            'estado' => 'disponible',
            'venta_id' => null,
            'cliente_id' => null,
            'fecha_ingreso' => now(),
            'fecha_venta' => null,
        ]);

        $venta = $this->crearVenta(1);

        $this->devolver($venta, 1);

        $this->assertSame(11, (int) $this->producto->fresh()->stock);
        $this->assertDatabaseCount('numeros_serie', 1);

        $intacta->refresh();

        $this->assertSame('disponible', $intacta->estado);
        $this->assertNull($intacta->venta_id);
        $this->assertNull($intacta->fecha_venta);
    }
}
