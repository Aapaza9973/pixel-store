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
}
