<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Marca;
use App\Models\NumeroSerie;
use App\Models\Pago;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreVentaTest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $categoria = Categoria::create(['nombre' => 'Categoría Store Venta', 'tipo' => 'Componente']);
        $marca = Marca::create(['nombre' => 'Marca Store Venta']);

        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'marca_id' => $marca->id,
            'nombre' => 'Monitor Store Venta',
            'precio_unitario' => 1000,
            'stock' => 5,
            'umbral_alerta' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function payload(array $cambios = []): array
    {
        $base = [
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 1],
            ],
            'pagos' => [
                ['metodo' => 'Efectivo', 'monto' => 1000],
            ],
            'meta' => [
                'operacion_id' => (string) Str::uuid(),
            ],
        ];

        // array_replace_recursive mezclaría los items índice por índice, así
        // que se reemplazan las claves de primer nivel explícitamente.
        return array_merge($base, $cambios);
    }

    public function test_descuento_dentro_del_limite_se_aplica_y_persiste(): void
    {
        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload([
                'descuento_porcentaje' => 10,
                'pagos' => [['metodo' => 'Efectivo', 'monto' => 900]],
            ])
        );

        $response->assertCreated();

        $venta = $response->json('venta');

        // El total es el que devuelve el servidor: 1000 - 10% = 900.
        $this->assertSame(900.0, (float) $venta['total']);
        $this->assertSame(100.0, (float) $venta['descuento']);
        $this->assertSame(10.0, (float) $venta['descuento_porcentaje']);

        $this->assertDatabaseHas('ventas', [
            'id' => $venta['id'],
            'total' => 900,
            'descuento' => 100,
            'descuento_porcentaje' => 10,
            'autorizado_por' => null,
        ]);

        $this->assertSame(4, (int) $this->producto->fresh()->stock);
    }

    public function test_descuento_sobre_el_limite_del_rol_devuelve_422(): void
    {
        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload([
                'descuento_porcentaje' => 15,
                'pagos' => [['metodo' => 'Efectivo', 'monto' => 850]],
            ])
        );

        $response->assertStatus(422);
        $this->assertStringContainsString('autorización', (string) $response->json('message'));

        $this->assertSame(0, Venta::count());
        $this->assertSame(5, (int) $this->producto->fresh()->stock);
    }

    public function test_descuento_porcentaje_fuera_de_rango_devuelve_422(): void
    {
        $excedido = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload(['descuento_porcentaje' => 150])
        );

        $excedido->assertStatus(422);
        $excedido->assertJsonValidationErrors('descuento_porcentaje');

        $negativo = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload(['descuento_porcentaje' => -5])
        );

        $negativo->assertStatus(422);
        $negativo->assertJsonValidationErrors('descuento_porcentaje');

        $this->assertSame(0, Venta::count());
    }

    public function test_venta_sin_descuento_usa_el_subtotal_como_total(): void
    {
        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload()
        );

        $response->assertCreated();

        $venta = $response->json('venta');

        $this->assertSame(1000.0, (float) $venta['total']);
        $this->assertSame(0.0, (float) $venta['descuento']);
        $this->assertSame(0.0, (float) $venta['descuento_porcentaje']);

        $this->assertDatabaseHas('ventas', [
            'id' => $venta['id'],
            'total' => 1000,
            'descuento' => 0,
        ]);
    }

    public function test_venta_con_cliente_id_persiste_el_cliente(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Cliente POS',
            'nit_ci' => '7788990',
            'telefono' => '+591 70055566',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload([
                'meta' => [
                    'operacion_id' => (string) Str::uuid(),
                    'cliente_id' => $cliente->id,
                ],
            ])
        );

        $response->assertCreated();

        $this->assertDatabaseHas('ventas', [
            'id' => $response->json('venta.id'),
            'cliente_id' => $cliente->id,
        ]);
    }

    public function test_venta_sin_cliente_id_permite_confirmar(): void
    {
        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload()
        );

        $response->assertCreated();

        $this->assertDatabaseHas('ventas', [
            'id' => $response->json('venta.id'),
            'cliente_id' => null,
        ]);
    }

    private function crearSerie(string $numero, string $estado = 'disponible'): NumeroSerie
    {
        return NumeroSerie::create([
            'producto_id' => $this->producto->id,
            'numero_serie' => $numero,
            'estado' => $estado,
            'fecha_ingreso' => now(),
        ]);
    }

    public function test_venta_con_numero_serie_id_marca_la_serie_como_vendida(): void
    {
        $serie = $this->crearSerie('SN-POS-001');

        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload([
                'items' => [[
                    'producto_id' => $this->producto->id,
                    'cantidad' => 1,
                    'numero_serie_id' => $serie->id,
                ]],
            ])
        );

        $response->assertCreated();

        $serie->refresh();
        $this->assertSame('vendido', $serie->estado);
        $this->assertSame($response->json('venta.id'), $serie->venta_id);
        $this->assertNotNull($serie->fecha_venta);
    }

    public function test_endpoint_series_devuelve_solo_disponibles(): void
    {
        $this->crearSerie('SN-DISPONIBLE-1');
        $this->crearSerie('SN-VENDIDA-1', 'vendido');

        $response = $this->actingAs($this->vendedor)
            ->getJson(route('admin.productos.series', $this->producto));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.numero_serie', 'SN-DISPONIBLE-1');
    }

    public function test_venta_sin_numero_serie_id_sigue_funcionando(): void
    {
        $serie = $this->crearSerie('SN-OPCIONAL-1');

        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload()
        );

        $response->assertCreated();

        $this->assertDatabaseHas('detalle_ventas', [
            'venta_id' => $response->json('venta.id'),
            'producto_id' => $this->producto->id,
            'numero_serie_id' => null,
        ]);

        $this->assertSame('disponible', $serie->fresh()->estado);
    }

    public function test_multi_pago_suma_correcta_permite_confirmar(): void
    {
        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload([
                'pagos' => [
                    ['metodo' => 'Efectivo', 'monto' => 600],
                    ['metodo' => 'Tarjeta', 'monto' => 400, 'referencia' => 'POS-123'],
                ],
            ])
        );

        $response->assertCreated();

        $ventaId = $response->json('venta.id');

        $this->assertSame(2, Pago::where('venta_id', $ventaId)->count());
        $this->assertSame(1000.0, (float) Pago::where('venta_id', $ventaId)->sum('monto'));
        $this->assertSame('POS-123', Pago::where('venta_id', $ventaId)->where('metodo', 'Tarjeta')->value('referencia'));
    }

    public function test_multi_pago_con_suma_insuficiente_devuelve_422(): void
    {
        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload([
                'pagos' => [
                    ['metodo' => 'Efectivo', 'monto' => 600],
                    ['metodo' => 'QR', 'monto' => 300],
                ],
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'pago_insuficiente');

        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(5, (int) $this->producto->fresh()->stock);
    }

    public function test_stock_insuficiente_devuelve_409(): void
    {
        $this->producto->update(['stock' => 1]);

        $response = $this->actingAs($this->vendedor)->postJson(
            route('admin.ventas.store'),
            $this->payload([
                'items' => [['producto_id' => $this->producto->id, 'cantidad' => 2]],
                'pagos' => [['metodo' => 'Efectivo', 'monto' => 2000]],
            ])
        );

        $response->assertStatus(409);
        $response->assertJsonStructure(['message', 'code']);
        $response->assertJsonPath('code', 'stock_insuficiente');

        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(1, (int) $this->producto->fresh()->stock);
    }

    public function test_usuario_sin_permiso_no_puede_confirmar(): void
    {
        $inventario = User::factory()->create(['activo' => true]);
        $inventario->assignRole('Inventario');

        $response = $this->actingAs($inventario)->postJson(
            route('admin.ventas.store'),
            $this->payload()
        );

        $response->assertForbidden();
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(5, (int) $this->producto->fresh()->stock);
    }

    public function test_reintento_con_mismo_operacion_id_no_duplica_venta_ni_descuento(): void
    {
        $payload = $this->payload([
            'descuento_porcentaje' => 10,
            'pagos' => [['metodo' => 'Efectivo', 'monto' => 900]],
        ]);

        $primera = $this->actingAs($this->vendedor)->postJson(route('admin.ventas.store'), $payload);
        $segunda = $this->actingAs($this->vendedor)->postJson(route('admin.ventas.store'), $payload);

        $primera->assertCreated();
        $segunda->assertCreated();

        $this->assertSame(
            $primera->json('venta.id'),
            $segunda->json('venta.id'),
            'El reintento debe devolver la misma venta.'
        );

        $this->assertSame(1, Venta::count());
        $this->assertSame(900.0, (float) $segunda->json('venta.total'));

        $venta = Venta::firstOrFail();
        $this->assertSame('100.00', $venta->descuento);
        $this->assertSame(4, (int) $this->producto->fresh()->stock);
    }
}
