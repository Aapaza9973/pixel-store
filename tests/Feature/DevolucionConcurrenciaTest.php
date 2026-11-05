<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DomainException;
use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\DevolucionService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 5.14.11: dos devoluciones concurrentes del mismo venta_detalle_id con
 * operacion_id distintos no pueden superar lo vendido.
 *
 * No usa DatabaseTransactions a propósito: la conexión secundaria solo puede
 * lockear/actualizar filas ya commiteadas, y el patrón D17 depende de eso.
 * La limpieza va en finally para no dejar filas en la base de tests.
 */
class DevolucionConcurrenciaTest extends TestCase
{
    public function test_dos_devoluciones_concurrentes_del_mismo_item_no_superan_lo_vendido(): void
    {
        $vendedor = User::factory()->create(['activo' => true]);

        $categoria = Categoria::create([
            'nombre' => 'Categoría Concurrencia Devolución',
            'tipo' => 'Componente',
        ]);

        // Stock 1 = estado posterior a vender 3; la primera devolución (la de la
        // conexión secundaria) reingresa 2 y lo deja en 3.
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Mouse Concurrencia',
            'precio_unitario' => 100,
            'stock' => 1,
            'umbral_alerta' => 1,
        ]);

        $venta = Venta::create([
            'operacion_id' => (string) Str::uuid(),
            'user_id' => $vendedor->id,
            'total' => 300,
            'descuento' => 0,
            'descuento_porcentaje' => 0,
            'estado' => 'Pagado',
        ]);

        $detalle = DetalleVenta::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'cantidad' => 3,
            'precio_unitario' => 100,
        ]);

        $pdo = $this->conexionSecundaria();
        $operacionPrimera = (string) Str::uuid();
        $pendiente = true;

        try {
            // Red de seguridad: si el lock no se comporta, el test falla por
            // timeout de sentencia en vez de colgar la suite.
            DB::statement("SET statement_timeout = '10s'");

            // La primera devolución (2 de 3) queda abierta en otra conexión.
            // Sin triggers de FK en esta sesión: el INSERT de la línea tomaría
            // un FOR KEY SHARE sobre detalle_ventas y bloquearía al servicio
            // antes de que el hook pudiera commitear.
            $pdo->exec('SET session_replication_role = replica');
            $pdo->beginTransaction();
            $this->insertarPrimeraDevolucion($pdo, $operacionPrimera, $venta->id, $detalle->id, $producto->id, $vendedor->id);

            // Hook determinístico: en cuanto el servicio toma el lock de la
            // línea, la otra conexión commitea. Sin lockForUpdate esta consulta
            // no existe, el hook no dispara y el servicio confirma de más.
            DB::listen(function (QueryExecuted $query) use (&$pendiente, $pdo): void {
                if (! $pendiente) {
                    return;
                }

                $sql = strtolower($query->sql);

                if (str_contains($sql, 'detalle_ventas') && str_contains($sql, 'for update')) {
                    $pendiente = false;
                    $pdo->commit();
                }
            });

            try {
                app(DevolucionService::class)->registrar(
                    $venta->id,
                    [['venta_detalle_id' => $detalle->id, 'cantidad' => 2]],
                    'Segunda devolución concurrente',
                    (string) Str::uuid(),
                    $vendedor->id
                );

                $this->fail('Se esperaba DomainException: entre las dos devoluciones suman 4 de 3 vendidas.');
            } catch (DomainException $e) {
                $this->assertStringContainsString('Solo queda 1 unidad por devolver', $e->getMessage());
            }

            $this->assertFalse($pendiente, 'El servicio debió tomar el lock de detalle_ventas antes de contar lo devuelto.');

            // Solo quedó la devolución de la otra conexión.
            $this->assertDatabaseCount('devoluciones', 1);
            $this->assertDatabaseCount('devolucion_detalles', 1);

            // La transacción de la segunda revirtió: no reingresó stock de más
            // ni volvió a cambiar el estado de la venta.
            $this->assertSame(3, (int) $producto->fresh()->stock);
            $this->assertSame('parcialmente_devuelta', $venta->fresh()->estado);
        } finally {
            $pendiente = false;

            try {
                DB::statement('SET statement_timeout = 0');
            } catch (\Throwable) {
                // La conexión pudo quedar inutilizable si el lock expiró.
            }

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->limpiar($venta->id, $producto->id, $categoria->id, $vendedor->id);
        }
    }

    /**
     * Inserta la devolución que gana la carrera, con los mismos efectos que
     * DevolucionService (cabecera, línea, stock y estado de la venta).
     *
     * Todo queda dentro de la transacción abierta: el COMMIT lo hace el hook
     * cuando el servicio ya tomó el lock de la línea.
     */
    private function insertarPrimeraDevolucion(
        \PDO $pdo,
        string $operacionId,
        int $ventaId,
        int $detalleId,
        int $productoId,
        int $userId
    ): void {
        $insert = $pdo->prepare(
            "INSERT INTO devoluciones (operacion_id, venta_id, user_id, motivo, estado, total_devuelto, created_at, updated_at)
             VALUES (CAST(:operacion_id AS uuid), :venta_id, :user_id, 'Primera devolución concurrente', 'procesada', 200, now(), now())
             RETURNING id"
        );
        $insert->execute([
            'operacion_id' => $operacionId,
            'venta_id' => $ventaId,
            'user_id' => $userId,
        ]);

        $devolucionId = (int) $insert->fetchColumn();

        $pdo->prepare(
            "INSERT INTO devolucion_detalles (devolucion_id, venta_id, venta_detalle_id, producto_id, cantidad, motivo, estado, monto, created_at, updated_at)
             VALUES (:devolucion_id, :venta_id, :detalle_id, :producto_id, 2, 'Primera devolución concurrente', 'Pendiente', 200, now(), now())"
        )->execute([
            'devolucion_id' => $devolucionId,
            'venta_id' => $ventaId,
            'detalle_id' => $detalleId,
            'producto_id' => $productoId,
        ]);

        $pdo->prepare('UPDATE productos SET stock = stock + 2, updated_at = now() WHERE id = :id')
            ->execute(['id' => $productoId]);

        $pdo->prepare("UPDATE ventas SET estado = 'parcialmente_devuelta', updated_at = now() WHERE id = :id")
            ->execute(['id' => $ventaId]);
    }

    private function limpiar(int $ventaId, int $productoId, int $categoriaId, int $userId): void
    {
        DB::table('devolucion_detalles')->where('venta_id', $ventaId)->delete();
        DB::table('devoluciones')->where('venta_id', $ventaId)->delete();
        DB::table('detalle_ventas')->where('venta_id', $ventaId)->delete();
        DB::table('ventas')->where('id', $ventaId)->delete();
        DB::table('productos')->where('id', $productoId)->delete();
        DB::table('categorias')->where('id', $categoriaId)->delete();
        DB::table('users')->where('id', $userId)->delete();
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
