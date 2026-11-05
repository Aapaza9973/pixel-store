<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DomainException;
use App\Models\CierreCaja;
use App\Models\Pago;
use App\Models\User;
use App\Models\Venta;
use App\Services\CierreCajaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class CierreCajaServiceTest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    private CierreCajaService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendedor = User::factory()->create();
        $this->servicio = app(CierreCajaService::class);
    }

    private function crearVenta(string $estado, float $total, ?User $vendedor = null): Venta
    {
        return Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'user_id' => ($vendedor ?? $this->vendedor)->id,
            'total' => $total,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => $estado,
        ]);
    }

    private function crearPago(Venta $venta, string $metodo, float $monto): Pago
    {
        return Pago::create([
            'venta_id' => $venta->id,
            'monto' => $monto,
            'metodo' => $metodo,
            'estado' => 'Completado',
        ]);
    }

    public function test_calcular_del_dia_suma_pagos_por_metodo(): void
    {
        $venta = $this->crearVenta('Pagado', 1000);
        $this->crearPago($venta, 'Efectivo', 600);
        $this->crearPago($venta, 'Tarjeta', 400);

        $datos = $this->servicio->calcularDelDia(now()->toDateString(), $this->vendedor->id);

        $this->assertSame(1000.0, $datos['total_ventas']);
        $this->assertSame(600.0, $datos['por_metodo']['Efectivo']);
        $this->assertSame(400.0, $datos['por_metodo']['Tarjeta']);
        $this->assertSame(0.0, $datos['por_metodo']['Transferencia']);
        $this->assertSame(0.0, $datos['por_metodo']['QR']);
        $this->assertSame(0.0, $datos['por_metodo']['Otro']);
        $this->assertSame(600.0, $datos['efectivo_esperado']);
    }

    public function test_calcular_ignora_ventas_canceladas_y_pendientes(): void
    {
        $cancelada = $this->crearVenta('Cancelada', 500);
        $this->crearPago($cancelada, 'Efectivo', 500);

        $pendiente = $this->crearVenta('Pendiente', 300);
        $this->crearPago($pendiente, 'Tarjeta', 300);

        $datos = $this->servicio->calcularDelDia(now()->toDateString(), $this->vendedor->id);

        $this->assertSame(0.0, $datos['total_ventas']);
        $this->assertSame(0.0, $datos['por_metodo']['Efectivo']);
        $this->assertSame(0.0, $datos['por_metodo']['Tarjeta']);
        $this->assertSame(0.0, $datos['efectivo_esperado']);
    }

    public function test_cerrar_guarda_diferencia_correcta(): void
    {
        $venta = $this->crearVenta('Pagado', 600);
        $this->crearPago($venta, 'Efectivo', 600);

        $cierre = $this->servicio->cerrar(
            $this->vendedor->id,
            now()->toDateString(),
            'mañana',
            ['efectivo' => 590]
        );

        $this->assertSame(600.0, (float) $cierre->efectivo_esperado);
        $this->assertSame(590.0, (float) $cierre->efectivo_real);
        $this->assertSame(-10.0, (float) $cierre->diferencia);

        $this->assertCount(5, $cierre->pagos);

        $efectivo = $cierre->pagos->firstWhere('metodo', 'Efectivo');
        $this->assertSame(600.0, (float) $efectivo->monto_sistema);
        $this->assertSame(590.0, (float) $efectivo->monto_real);
    }

    public function test_cerrar_duplicado_lanza_excepcion(): void
    {
        $venta = $this->crearVenta('Pagado', 600);
        $this->crearPago($venta, 'Efectivo', 600);

        $this->servicio->cerrar($this->vendedor->id, now()->toDateString(), 'mañana', ['efectivo' => 600]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Ya existe un cierre para este turno.');

        try {
            $this->servicio->cerrar($this->vendedor->id, now()->toDateString(), 'mañana', ['efectivo' => 600]);
        } finally {
            $this->assertSame(1, CierreCaja::where('user_id', $this->vendedor->id)->count());
        }
    }
}
