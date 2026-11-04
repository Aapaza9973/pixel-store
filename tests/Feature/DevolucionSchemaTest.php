<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Devolucion;
use App\Models\DevolucionDetalle;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DevolucionSchemaTest extends TestCase
{
    use DatabaseTransactions;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
    }

    public function test_migraciones_crean_las_tablas(): void
    {
        $this->assertTrue(Schema::hasTable('devoluciones'), 'Falta la tabla devoluciones.');
        $this->assertTrue(Schema::hasTable('devolucion_detalles'), 'Falta la tabla devolucion_detalles.');

        $this->assertTrue(
            Schema::hasColumns('devoluciones', ['venta_id', 'user_id', 'motivo', 'estado', 'total_devuelto']),
            'La cabecera de devolución no tiene las columnas del alcance 5.14.'
        );

        $this->assertTrue(
            Schema::hasColumns('devolucion_detalles', ['devolucion_id', 'venta_detalle_id', 'cantidad', 'monto']),
            'Las líneas de devolución no tienen las columnas del alcance 5.14.'
        );
    }

    public function test_modelo_devolucion_relaciona_venta_y_detalles(): void
    {
        $categoria = Categoria::create(['nombre' => 'Categoría Devoluciones', 'tipo' => 'Componente']);

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Monitor Devoluciones',
            'precio_unitario' => 150,
            'stock' => 3,
            'umbral_alerta' => 1,
        ]);

        $venta = Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'user_id' => $this->vendedor->id,
            'total' => 150,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
        ]);

        $detalleVenta = DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 150,
        ]);

        $devolucion = Devolucion::create([
            'operacion_id' => (string) Str::uuid(),
            'venta_id' => $venta->id,
            'user_id' => $this->vendedor->id,
            'motivo' => 'Producto con falla de fábrica',
            'estado' => 'procesada',
            'total_devuelto' => 150,
        ]);

        $detalle = DevolucionDetalle::create([
            'devolucion_id' => $devolucion->id,
            'venta_detalle_id' => $detalleVenta->id,
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'motivo' => 'Falla de fábrica',
            'monto' => 150,
        ]);

        $devolucion->load(['venta', 'user', 'detalles']);

        $this->assertTrue($devolucion->venta->is($venta));
        $this->assertTrue($devolucion->user->is($this->vendedor));
        $this->assertCount(1, $devolucion->detalles);
        $this->assertTrue($devolucion->detalles->first()->is($detalle));
        $this->assertSame('150.00', $devolucion->total_devuelto);
        $this->assertTrue($devolucion->estaProcesada());
        $this->assertFalse($devolucion->estaAnulada());

        $detalle->load('ventaDetalle');

        $this->assertTrue($detalle->ventaDetalle->is($detalleVenta));
        $this->assertSame('150.00', $detalle->monto);
        $this->assertSame(1, $devolucion->fresh()->detalles()->count());
    }

    public function test_borrar_venta_con_devoluciones_es_rechazado(): void
    {
        $categoria = Categoria::create(['nombre' => 'Categoría Trazabilidad', 'tipo' => 'Componente']);

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Monitor Trazabilidad',
            'precio_unitario' => 150,
            'stock' => 3,
            'umbral_alerta' => 1,
        ]);

        $venta = Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'user_id' => $this->vendedor->id,
            'total' => 150,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
        ]);

        $detalleVenta = DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 150,
        ]);

        $devolucion = Devolucion::create([
            'operacion_id' => (string) Str::uuid(),
            'venta_id' => $venta->id,
            'user_id' => $this->vendedor->id,
            'motivo' => 'Producto con falla',
            'estado' => 'procesada',
            'total_devuelto' => 150,
        ]);

        DevolucionDetalle::create([
            'devolucion_id' => $devolucion->id,
            'venta_detalle_id' => $detalleVenta->id,
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'motivo' => 'Falla',
            'monto' => 150,
        ]);

        // En Postgres, un DELETE que viola la FK aborta la transacción (25P02).
        // DB::transaction usa un savepoint, así que después del catch la
        // conexión sigue usable para las aserciones.
        try {
            DB::transaction(fn () => DB::table('ventas')->where('id', $venta->id)->delete());

            $this->fail('Se esperaba QueryException: la venta tiene devoluciones.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('foreign key', strtolower($e->getMessage()));
        }

        $this->assertDatabaseHas('ventas', ['id' => $venta->id]);
        $this->assertDatabaseHas('devoluciones', ['id' => $devolucion->id]);
    }
}
