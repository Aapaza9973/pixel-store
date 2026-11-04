<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DomainException;
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
}
