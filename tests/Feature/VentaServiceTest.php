<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\PagoInsuficienteException;
use App\Exceptions\SerieNoDisponibleException;
use App\Exceptions\StockInsuficienteException;
use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Marca;
use App\Models\NumeroSerie;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentaService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class VentaServiceTest extends TestCase
{
    use DatabaseTransactions;

    private VentaService $service;

    private User $vendedor;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->service = app(VentaService::class);
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Ventas', 'tipo' => 'Componente']);
        $marca = Marca::create(['nombre' => 'Marca Ventas']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'SSD Ventas 1TB',
            'precio_unitario' => 100,
            'stock' => 5,
            'umbral_alerta' => 1,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $pagos
     * @param  array<string, mixed>  $meta
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>, 2: array<string, mixed>}
     */
    private function datos(array $items = [], array $pagos = [], array $meta = []): array
    {
        return [
            $items !== [] ? $items : [['producto_id' => $this->producto->id, 'cantidad' => 1]],
            $pagos !== [] ? $pagos : [['metodo' => 'Efectivo', 'monto' => 100]],
            array_merge([
                'cliente_id' => null,
                'vendedor_id' => $this->vendedor->id,
                'operacion_id' => (string) Str::uuid(),
            ], $meta),
        ];
    }

    public function test_confirmar_crea_venta_con_detalle_y_pagos(): void
    {
        [$items, $pagos, $meta] = $this->datos();

        $venta = $this->service->confirmar($items, $pagos, $meta);

        $this->assertDatabaseHas('ventas', [
            'id' => $venta->id,
            'operacion_id' => $meta['operacion_id'],
            'estado' => 'Pagado',
            'total' => 100,
        ]);

        $this->assertSame(1, DetalleVenta::where('venta_id', $venta->id)->count());
        $this->assertSame(1, Pago::where('venta_id', $venta->id)->count());
        $this->assertSame(4, (int) $this->producto->fresh()->stock);
    }

    public function test_confirmar_rechaza_stock_insuficiente(): void
    {
        $this->producto->update(['stock' => 1]);

        [$items, $pagos, $meta] = $this->datos(
            [['producto_id' => $this->producto->id, 'cantidad' => 2]],
            [['metodo' => 'Efectivo', 'monto' => 200]]
        );

        try {
            $this->service->confirmar($items, $pagos, $meta);
            $this->fail('Se esperaba StockInsuficienteException.');
        } catch (StockInsuficienteException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }

        $this->assertDatabaseMissing('ventas', ['operacion_id' => $meta['operacion_id']]);
        $this->assertSame(1, (int) $this->producto->fresh()->stock);
    }

    public function test_confirmar_es_idempotente_con_mismo_operacion_id(): void
    {
        [$items, $pagos, $meta] = $this->datos();

        $primera = $this->service->confirmar($items, $pagos, $meta);
        $segunda = $this->service->confirmar($items, $pagos, $meta);

        $this->assertSame($primera->id, $segunda->id);
        $this->assertSame(1, Venta::where('operacion_id', $meta['operacion_id'])->count());
        $this->assertSame(4, (int) $this->producto->fresh()->stock);
    }

    public function test_confirmar_rechaza_si_falta_pago(): void
    {
        [$items, $pagos, $meta] = $this->datos(
            [],
            [['metodo' => 'Efectivo', 'monto' => 90]]
        );

        try {
            $this->service->confirmar($items, $pagos, $meta);
            $this->fail('Se esperaba PagoInsuficienteException.');
        } catch (PagoInsuficienteException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        $this->assertDatabaseMissing('ventas', ['operacion_id' => $meta['operacion_id']]);
        $this->assertSame(5, (int) $this->producto->fresh()->stock);
    }

    public function test_confirmar_valida_sn_si_se_especifica(): void
    {
        $serie = NumeroSerie::create([
            'producto_id' => $this->producto->id,
            'numero_serie' => 'SN-VENDIDO-001',
            'estado' => 'vendido',
            'fecha_ingreso' => now(),
        ]);

        [$items, $pagos, $meta] = $this->datos(
            [['producto_id' => $this->producto->id, 'cantidad' => 1, 'numero_serie_id' => $serie->id]]
        );

        try {
            $this->service->confirmar($items, $pagos, $meta);
            $this->fail('Se esperaba SerieNoDisponibleException.');
        } catch (SerieNoDisponibleException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }

        $this->assertDatabaseMissing('ventas', ['operacion_id' => $meta['operacion_id']]);
        $this->assertSame(5, (int) $this->producto->fresh()->stock);
    }

    public function test_confirmar_marca_sn_como_vendido(): void
    {
        $serie = NumeroSerie::create([
            'producto_id' => $this->producto->id,
            'numero_serie' => 'SN-DISPONIBLE-001',
            'estado' => 'disponible',
            'fecha_ingreso' => now(),
        ]);

        [$items, $pagos, $meta] = $this->datos(
            [['producto_id' => $this->producto->id, 'cantidad' => 1, 'numero_serie_id' => $serie->id]]
        );

        $venta = $this->service->confirmar($items, $pagos, $meta);

        $serie->refresh();

        $this->assertSame('vendido', $serie->estado);
        $this->assertSame($venta->id, $serie->venta_id);
        $this->assertNotNull($serie->fecha_venta);
    }

    public function test_confirmar_usa_lockforupdate_ordenado(): void
    {
        $segundo = Producto::create([
            'categoria_id' => $this->producto->categoria_id,
            'nombre' => 'Gabinete Ventas',
            'precio_unitario' => 50,
            'stock' => 3,
            'umbral_alerta' => 1,
        ]);

        // Se envían en orden inverso al id para comprobar que el bloqueo se
        // ordena internamente y no se bloquea en cruz con otra venta.
        [$items, $pagos, $meta] = $this->datos(
            [
                ['producto_id' => $segundo->id, 'cantidad' => 2],
                ['producto_id' => $this->producto->id, 'cantidad' => 1],
            ],
            [['metodo' => 'Efectivo', 'monto' => 200]]
        );

        $venta = $this->service->confirmar($items, $pagos, $meta);

        $this->assertSame(2, DetalleVenta::where('venta_id', $venta->id)->count());
        $this->assertSame(4, (int) $this->producto->fresh()->stock);
        $this->assertSame(1, (int) $segundo->fresh()->stock);
    }
}
