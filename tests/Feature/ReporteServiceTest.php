<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DomainException;
use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\ReporteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * HU-6.2 (6.2.1-6.2.2): totales por período sobre ventas cobradas.
 */
class ReporteServiceTest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    private ReporteService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendedor = User::factory()->create();
        $this->servicio = app(ReporteService::class);
    }

    private function crearVenta(string $fecha, float $total, string $estado = 'Pagado'): Venta
    {
        return Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'fecha' => $fecha.' 10:00:00',
            'user_id' => $this->vendedor->id,
            'total' => $total,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => $estado,
        ]);
    }

    public function test_por_periodo_diario_agrupa_por_dia(): void
    {
        $this->crearVenta('2026-03-02', 100);
        $this->crearVenta('2026-03-02', 50);
        $this->crearVenta('2026-03-04', 300);
        // Una devuelta parcialmente sí se cobró; la cancelada no cuenta.
        $this->crearVenta('2026-03-04', 200, 'parcialmente_devuelta');
        $this->crearVenta('2026-03-04', 999, 'Cancelada');
        $this->crearVenta('2026-03-04', 777, 'Pendiente');

        $filas = $this->servicio->porPeriodo('2026-03-01', '2026-03-31', 'dia');

        $this->assertSame([
            ['periodo' => '2026-03-02', 'total_ventas' => 150.0, 'cantidad_ventas' => 2],
            ['periodo' => '2026-03-04', 'total_ventas' => 500.0, 'cantidad_ventas' => 2],
        ], $filas);
    }

    public function test_por_periodo_mensual_usa_date_trunc(): void
    {
        $this->crearVenta('2026-01-15', 100);
        $this->crearVenta('2026-01-31', 50);
        $this->crearVenta('2026-02-10', 200);
        // Fuera del rango: no debe aparecer.
        $this->crearVenta('2025-12-31', 999);

        $filas = $this->servicio->porPeriodo('2026-01-01', '2026-02-28', 'mes');

        // DATE_TRUNC('month') junta días distintos del mismo mes.
        $this->assertSame([
            ['periodo' => '2026-01', 'total_ventas' => 150.0, 'cantidad_ventas' => 2],
            ['periodo' => '2026-02', 'total_ventas' => 200.0, 'cantidad_ventas' => 1],
        ], $filas);
    }

    public function test_agrupacion_invalida_lanza_excepcion(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('La agrupación «trimestre» no es válida.');

        $this->servicio->porPeriodo('2026-01-01', '2026-01-31', 'trimestre');
    }

    public function test_ventas_por_vendedor_agrupa_y_ordena(): void
    {
        $otro = User::factory()->create();

        // Vendedor principal: 2 ventas (100 + 50 = 150).
        $this->crearVenta('2026-03-01', 100);
        $this->crearVenta('2026-03-02', 50);

        // Otro vendedor: 1 venta de 500.
        Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'fecha' => '2026-03-03 10:00:00',
            'user_id' => $otro->id,
            'total' => 500,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
        ]);

        $filas = $this->servicio->ventasPorVendedor('2026-03-01', '2026-03-31');

        $this->assertCount(2, $filas);
        $this->assertSame($otro->id, $filas[0]['vendedor_id']);
        $this->assertSame(500.0, $filas[0]['total']);
        $this->assertSame($this->vendedor->id, $filas[1]['vendedor_id']);
        $this->assertSame(150.0, $filas[1]['total']);
        $this->assertSame(2, $filas[1]['cantidad']);
    }

    public function test_ventas_por_categoria_suma_por_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'Procesadores '.uniqid(), 'tipo' => 'Componente']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'CPU '.uniqid(),
            'precio_unitario' => 1000,
            'stock' => 10,
            'umbral_alerta' => 5,
        ]);

        $venta = $this->crearVenta('2026-03-02', 2000);
        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => 1000,
            'descuento_item' => 0,
        ]);

        $filas = $this->servicio->ventasPorCategoria('2026-03-01', '2026-03-31');

        $this->assertCount(1, $filas);
        $this->assertSame($categoria->nombre, $filas[0]['categoria']);
        $this->assertSame(2, $filas[0]['cantidad']);
        $this->assertSame(2000.0, $filas[0]['total']);
    }

    public function test_metodos_pago_mas_usados_cuenta_y_suma(): void
    {
        $venta = $this->crearVenta('2026-03-02', 300);
        Pago::create(['venta_id' => $venta->id, 'monto' => 200, 'metodo' => 'Efectivo', 'estado' => 'Completado']);
        Pago::create(['venta_id' => $venta->id, 'monto' => 100, 'metodo' => 'Tarjeta', 'estado' => 'Completado']);

        $filas = $this->servicio->metodosPagoMasUsados('2026-03-01', '2026-03-31');

        $this->assertCount(2, $filas);
        $this->assertSame('Efectivo', $filas[0]['metodo']);
        $this->assertSame(1, $filas[0]['cantidad']);
        $this->assertSame(200.0, $filas[0]['total']);
    }

    private function crearProducto(string $nombre, float $precio, int $stock = 10, ?float $costo = null): Producto
    {
        $categoria = Categoria::create(['nombre' => 'Cat '.uniqid(), 'tipo' => 'Componente']);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio_unitario' => $precio,
            'costo' => $costo,
            'stock' => $stock,
            'umbral_alerta' => 2,
        ]);
    }

    public function test_utilidad_neta_por_producto(): void
    {
        $conCosto = $this->crearProducto('Con costo '.uniqid(), 1000, 10, 600);
        $sinCosto = $this->crearProducto('Sin costo '.uniqid(), 500, 10, null);

        $venta = $this->crearVenta('2026-03-02', 2500);
        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $conCosto->id,
            'cantidad' => 2,
            'precio_unitario' => 1000,
            'precio_costo' => 600,
            'descuento_item' => 0,
        ]);
        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $sinCosto->id,
            'cantidad' => 1,
            'precio_unitario' => 500,
            'precio_costo' => null,
            'descuento_item' => 0,
        ]);

        $filas = $this->servicio->utilidadNetaPorProducto('2026-03-01', '2026-03-31');

        $conCostoFila = collect($filas)->firstWhere('producto_id', $conCosto->id);
        $this->assertSame(2000.0, $conCostoFila['ingresos']);
        $this->assertSame(1200.0, $conCostoFila['costo']);
        $this->assertSame(800.0, $conCostoFila['utilidad']);

        // Sin precio_costo histórico no se puede calcular la utilidad.
        $sinCostoFila = collect($filas)->firstWhere('producto_id', $sinCosto->id);
        $this->assertNull($sinCostoFila['costo']);
        $this->assertNull($sinCostoFila['utilidad']);
    }

    public function test_ranking_productos_top_y_bottom(): void
    {
        $barato = $this->crearProducto('Barato '.uniqid(), 100);
        $medio = $this->crearProducto('Medio '.uniqid(), 200);
        $caro = $this->crearProducto('Caro '.uniqid(), 300);

        $venta = $this->crearVenta('2026-03-02', 1300);
        foreach ([[$barato, 1], [$medio, 2], [$caro, 3]] as [$producto, $cantidad]) {
            DetalleVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $producto->precio_unitario,
                'descuento_item' => 0,
            ]);
        }

        $top = $this->servicio->rankingProductos('2026-03-01', '2026-03-31', 2);
        $this->assertCount(2, $top);
        $this->assertSame($caro->id, $top[0]['producto_id']);
        $this->assertSame(900.0, $top[0]['total']);
        $this->assertSame($medio->id, $top[1]['producto_id']);

        $bottom = $this->servicio->rankingProductos('2026-03-01', '2026-03-31', 2, 'asc');
        $this->assertSame($barato->id, $bottom[0]['producto_id']);
        $this->assertSame(100.0, $bottom[0]['total']);
    }

    public function test_antiguedad_inventario_solo_con_stock(): void
    {
        $viejo = $this->crearProducto('Viejo '.uniqid(), 100, 5);
        $nunca = $this->crearProducto('Nunca vendido '.uniqid(), 100, 3);
        $agotado = $this->crearProducto('Agotado '.uniqid(), 100, 0);

        $venta = $this->crearVenta(now()->subDays(10)->toDateString(), 100);
        DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $viejo->id,
            'cantidad' => 1,
            'precio_unitario' => 100,
            'descuento_item' => 0,
        ]);

        $filas = collect($this->servicio->antiguedadInventario());

        $this->assertSame(10, $filas->firstWhere('producto_id', $viejo->id)['dias_sin_venta']);
        $this->assertNull($filas->firstWhere('producto_id', $nunca->id)['dias_sin_venta']);
        // Sin stock queda fuera del reporte.
        $this->assertNull($filas->firstWhere('producto_id', $agotado->id));
    }

    /**
     * HU-6.2.6: el reporte anual debe resolverse en menos de 2 s con 1000 ventas.
     * Threshold holgado para no ser flaky en CI.
     */
    public function test_por_periodo_anual_con_1000_ventas_es_rapido(): void
    {
        $filas = [];
        $ahora = now()->toDateTimeString();

        for ($i = 0; $i < 1000; $i++) {
            $filas[] = [
                'operacion_id' => (string) Str::uuid(),
                'fecha' => sprintf('2031-%02d-%02d 10:00:00', ($i % 12) + 1, ($i % 28) + 1),
                'user_id' => $this->vendedor->id,
                'total' => 100,
                'descuento' => 0,
                'descuento_porcentaje' => 0,
                'estado' => 'Pagado',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        foreach (array_chunk($filas, 250) as $lote) {
            DB::table('ventas')->insert($lote);
        }

        $inicio = hrtime(true);
        $resultado = $this->servicio->porPeriodo('2031-01-01', '2031-12-31', 'anio');
        $duracionMs = (hrtime(true) - $inicio) / 1_000_000;

        $this->assertSame([
            ['periodo' => '2031', 'total_ventas' => 100000.0, 'cantidad_ventas' => 1000],
        ], $resultado);
        $this->assertLessThan(2000, $duracionMs, "porPeriodo tardó {$duracionMs} ms");
    }
}
