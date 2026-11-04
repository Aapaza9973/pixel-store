<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DomainException;
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

    public function test_confirmar_rechaza_descuento_fuera_de_rango(): void
    {
        // El FormRequest valida 0..100; este test cubre el clamp defensivo del
        // servicio para callers directos (comandos, jobs, API).
        foreach ([-10, 150] as $porcentaje) {
            [$items, $pagos, $meta] = $this->datos(meta: ['descuento_porcentaje' => $porcentaje]);

            try {
                $this->service->confirmar($items, $pagos, $meta);
                $this->fail("Se esperaba DomainException para el descuento {$porcentaje}%.");
            } catch (DomainException $e) {
                $this->assertStringContainsString('0 y 100', $e->getMessage());
                $this->assertSame(422, $e->getStatusCode());
            }

            $this->assertDatabaseCount('ventas', 0);
            $this->assertSame(5, (int) $this->producto->fresh()->stock);
        }
    }

    public function test_confirmar_valida_el_descuento_con_el_rol_recibido(): void
    {
        // Vendedor: 10 % de límite según RoleSeeder. Precio 100 → 15 % = 15.
        [$items, $pagos, $meta] = $this->datos(
            [],
            [['metodo' => 'Efectivo', 'monto' => 85]],
            ['descuento_porcentaje' => 15, 'rol_descuento' => 'Vendedor']
        );

        try {
            $this->service->confirmar($items, $pagos, $meta);
            $this->fail('Se esperaba DomainException por exceder el límite del rol.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('autorización', $e->getMessage());
            $this->assertSame(422, $e->getStatusCode());
        }

        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(5, (int) $this->producto->fresh()->stock);

        // El mismo caso con 10 % sí entra: el límite se aplica sobre el
        // subtotal calculado bajo lock.
        [$items, $pagos, $meta] = $this->datos(
            [],
            [['metodo' => 'Efectivo', 'monto' => 90]],
            ['descuento_porcentaje' => 10, 'rol_descuento' => 'Vendedor']
        );

        $venta = $this->service->confirmar($items, $pagos, $meta);

        $this->assertSame('90.00', $venta->total);
        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_confirmar_sin_rol_no_valida_limite_pero_si_clamp(): void
    {
        // Sin rol_descuento no hay límite por rol: caller interno de confianza.
        [$items, $pagos, $meta] = $this->datos(
            [],
            [['metodo' => 'Efectivo', 'monto' => 90]],
            ['descuento_porcentaje' => 10]
        );

        $venta = $this->service->confirmar($items, $pagos, $meta);

        $this->assertSame('90.00', $venta->total);

        // El clamp 0..100 sigue vigente aunque no haya rol.
        [$items, $pagos, $meta] = $this->datos(
            [],
            [['metodo' => 'Efectivo', 'monto' => 0.01]],
            ['descuento_porcentaje' => 150]
        );

        try {
            $this->service->confirmar($items, $pagos, $meta);
            $this->fail('Se esperaba DomainException por el clamp de descuento.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('0 y 100', $e->getMessage());
        }

        $this->assertDatabaseCount('ventas', 1);
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

    public function test_reintento_con_colision_de_unique_devuelve_la_venta_existente(): void
    {
        // El guard del servicio lee una vez y recién después inserta: en
        // producción dos requests pueden pasar el guard antes de que el primero
        // commitee. Acá se fuerza esa ventana de forma determinística: un
        // listener inserta la fila conflictiva desde otra conexión ya commiteada
        // justo antes del INSERT del servicio.
        $operacionId = (string) Str::uuid();
        $conflictoId = null;

        Venta::creating(function (Venta $venta) use ($operacionId, &$conflictoId): void {
            if ($venta->operacion_id !== $operacionId || $conflictoId !== null) {
                return;
            }

            $conflictoId = $this->insertarVentaDesdeOtraConexion($operacionId);
        });

        [$items, $pagos, $meta] = $this->datos(
            [],
            [['metodo' => 'Efectivo', 'monto' => 100]],
            ['operacion_id' => $operacionId]
        );

        try {
            $venta = $this->service->confirmar($items, $pagos, $meta);

            $this->assertNotNull($conflictoId, 'El listener debió insertar la fila conflictiva.');
            $this->assertSame($conflictoId, $venta->id);
            $this->assertSame($operacionId, $venta->operacion_id);
            $this->assertDatabaseCount('ventas', 1);

            // El intento revirtió: el stock se descontó solo en la venta original.
            $this->assertSame(5, (int) $this->producto->fresh()->stock);
        } finally {
            // La fila se commiteó desde otra conexión, así que DatabaseTransactions
            // no la limpia: se borra a mano para no contaminar el resto de la suite.
            if ($conflictoId !== null) {
                $this->borrarVentaDesdeOtraConexion($conflictoId);
            }
        }
    }

    public function test_dos_ventas_consecutivas_no_bajan_stock_a_negativo(): void
    {
        $this->producto->update(['stock' => 1]);

        [$items, $pagos, $meta] = $this->datos();

        $primera = $this->service->confirmar($items, $pagos, $meta);

        $this->assertDatabaseHas('ventas', ['id' => $primera->id, 'operacion_id' => $meta['operacion_id']]);
        $this->assertSame(0, (int) $this->producto->fresh()->stock);

        // Segunda venta del mismo producto con otra operación: ya no hay stock.
        [$items, $pagos, $meta] = $this->datos();

        try {
            $this->service->confirmar($items, $pagos, $meta);
            $this->fail('Se esperaba StockInsuficienteException en la segunda venta.');
        } catch (StockInsuficienteException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }

        $this->assertDatabaseCount('ventas', 1);
        $this->assertSame(0, (int) $this->producto->fresh()->stock);
    }

    public function test_rollback_completo_si_falla_mitad_del_payload(): void
    {
        // El primer producto tiene stock de sobra; el segundo no tiene ninguna
        // unidad. La validación falla ANTES de escribir, así que la venta
        // completa (y sus efectos) debe quedar sin rastro.
        $sinStock = Producto::create([
            'categoria_id' => $this->producto->categoria_id,
            'nombre' => 'Gabinete sin stock',
            'precio_unitario' => 50,
            'stock' => 0,
            'umbral_alerta' => 1,
        ]);

        $response = $this->actingAs($this->vendedor)->postJson(route('admin.ventas.store'), [
            'items' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 1],
                ['producto_id' => $sinStock->id, 'cantidad' => 1],
            ],
            'pagos' => [['metodo' => 'Efectivo', 'monto' => 150]],
            'meta' => ['operacion_id' => (string) Str::uuid()],
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('code', 'stock_insuficiente');

        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('ingresos', 0);
        $this->assertDatabaseCount('movimientos_stock', 0);
        $this->assertSame(5, (int) $this->producto->fresh()->stock);
        $this->assertSame(0, (int) $sinStock->fresh()->stock);
    }

    private function insertarVentaDesdeOtraConexion(string $operacionId): int
    {
        $pdo = $this->conexionSecundaria();

        // La fila se inserta fuera de la transacción del test para simular la
        // carrera real: su user_id todavía no está commiteado en la conexión
        // principal, así que se desactivan los chequeos de FK en esta sesión.
        // Es session-scoped y se descarta al cerrar la conexión.
        $pdo->exec('SET session_replication_role = replica');

        // 'Pagado' va como literal: el enum de Postgres no acepta un parámetro
        // de texto sin cast explícito.
        $insert = $pdo->prepare(
            "INSERT INTO ventas (operacion_id, fecha, user_id, total, descuento, descuento_porcentaje, estado, created_at, updated_at)
             VALUES (:operacion_id, now(), :user_id, 100, 0, 0, 'Pagado', now(), now())
             RETURNING id"
        );
        $insert->execute([
            'operacion_id' => $operacionId,
            'user_id' => $this->vendedor->id,
        ]);

        return (int) $insert->fetchColumn();
    }

    private function borrarVentaDesdeOtraConexion(int $ventaId): void
    {
        $pdo = $this->conexionSecundaria();
        $delete = $pdo->prepare('DELETE FROM ventas WHERE id = :id');
        $delete->execute(['id' => $ventaId]);
    }

    private function conexionSecundaria(): \PDO
    {
        $config = config('database.connections.pgsql');

        return new \PDO(
            sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['database']),
            $config['username'],
            $config['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }
}
