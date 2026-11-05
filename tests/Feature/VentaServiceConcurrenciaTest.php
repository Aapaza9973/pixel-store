<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\StockInsuficienteException;
use App\Models\Categoria;
use App\Models\Ingreso;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * 5.13.12: concurrencia REAL de dos ventas del último ítem en stock.
 *
 * Mecánica: un proceso PHP secundario (conexión PDO propia) abre una
 * transacción y confirma su venta con VentaService, que toma el
 * `FOR UPDATE` del producto. La transacción queda ABIERTA y se señala con un
 * archivo marcador; en ese punto el test llama a VentaService con otra
 * operación. El `lockForUpdate` de la principal queda esperando hasta que el
 * secundario commitea, y al desbloquearse Postgres devuelve la fila ya
 * actualizada (stock 0) → StockInsuficienteException.
 *
 * No usa DatabaseTransactions a propósito: el proceso secundario solo ve filas
 * ya commiteadas y él mismo commitea fuera de la transacción del test. La
 * limpieza va en finally, igual que DevolucionConcurrenciaTest.
 */
class VentaServiceConcurrenciaTest extends TestCase
{
    public function test_dos_ventas_concurrentes_del_ultimo_item_solo_una_gana(): void
    {
        $vendedor = User::factory()->create(['activo' => true]);

        // Nombre único: el test no usa DatabaseTransactions, así que un fallo
        // previo podría dejar filas y este create no debe chocar con ellas.
        $categoria = Categoria::create([
            'nombre' => 'Categoría Concurrencia Venta '.Str::random(8),
            'tipo' => 'Componente',
        ]);

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'GPU Última Unidad',
            'precio_unitario' => 100,
            'stock' => 1,
            'umbral_alerta' => 1,
        ]);

        $operacionGanadora = (string) Str::uuid();
        $operacionPerdedora = (string) Str::uuid();

        $marcador = storage_path('framework/testing/venta_concurrente_'.Str::random(10).'.lock');
        $marcadorFin = $marcador.'.done';
        $marcadorError = $marcador.'.error';
        $script = $marcador.'.php';

        file_put_contents($script, $this->scriptSecundario(
            $producto->id,
            $vendedor->id,
            $operacionGanadora,
            $marcador,
            $marcadorFin,
            $marcadorError
        ));

        $proceso = new Process(
            [PHP_BINARY, $script],
            base_path(),
            array_merge(getenv(), $this->envPostgres())
        );

        $ventaGanadoraId = null;
        $esperaDelLock = null;

        try {
            // Red de seguridad: si el lock no se comporta, el test falla por
            // timeout de sentencia en vez de colgar la suite.
            DB::statement("SET statement_timeout = '20s'");

            $proceso->start();

            $marcadorContenido = $this->esperarMarcador($marcador, $proceso, 20.0);

            if ($marcadorContenido === null) {
                $this->fail(
                    'El proceso secundario no tomó el lock del producto. '
                    .'stderr: '.$proceso->getErrorOutput().' stdout: '.$proceso->getOutput()
                );
            }

            [$baseSecundaria, $idGanador] = array_pad(explode('|', $marcadorContenido, 2), 2, '');
            $ventaGanadoraId = (int) $idGanador;

            $this->assertSame(
                config('database.connections.pgsql.database'),
                $baseSecundaria,
                'El proceso secundario debe usar la misma base que el test.'
            );

            // Venta B (perdedora): arranca mientras la A sigue sin commitear.
            $inicio = microtime(true);

            try {
                app(VentaService::class)->confirmar(
                    [['producto_id' => $producto->id, 'cantidad' => 1]],
                    [['metodo' => 'Efectivo', 'monto' => 100]],
                    [
                        'cliente_id' => null,
                        'vendedor_id' => $vendedor->id,
                        'operacion_id' => $operacionPerdedora,
                    ]
                );

                $this->fail('Se esperaba StockInsuficienteException: el último ítem ya estaba vendido.');
            } catch (StockInsuficienteException $e) {
                $esperaDelLock = microtime(true) - $inicio;
                $this->assertSame(409, $e->getStatusCode());
                $this->assertStringContainsString('Stock insuficiente', $e->getMessage());
            }

            $proceso->wait();

            $this->assertSame(0, $proceso->getExitCode(), 'El proceso secundario debió commitear sin errores.');
            $this->assertFileExists($marcadorFin);

            // Evidencia de concurrencia real: la principal quedó bloqueada en el
            // FOR UPDATE hasta que la otra transacción commiteó.
            $this->assertGreaterThanOrEqual(
                0.3,
                (float) $esperaDelLock,
                'La venta perdedora debió esperar el lock de la ganadora, no resolver antes.'
            );

            // No hubo oversell: una sola venta cobrada y el stock en 0.
            $this->assertSame(0, (int) $producto->fresh()->stock);
            $this->assertDatabaseCount('ventas', 1);
            $this->assertDatabaseHas('ventas', [
                'id' => $ventaGanadoraId,
                'operacion_id' => $operacionGanadora,
                'estado' => 'Pagado',
            ]);
            $this->assertDatabaseMissing('ventas', ['operacion_id' => $operacionPerdedora]);

            // El ganador tiene su asiento económico; el perdedor no dejó rastro.
            $this->assertDatabaseCount('ingresos', 1);
            $this->assertDatabaseHas('ingresos', [
                'venta_id' => $ventaGanadoraId,
                'user_id' => $vendedor->id,
                'monto' => 100,
                'tipo' => 'venta',
            ]);
            $this->assertSame(1, Venta::count());
            $this->assertSame(1, Ingreso::where('venta_id', $ventaGanadoraId)->count());
        } finally {
            if ($proceso->isRunning()) {
                $proceso->stop(1);
            }

            try {
                DB::statement('SET statement_timeout = 0');
            } catch (\Throwable) {
                // La conexión pudo quedar inutilizable si el lock expiró.
            }

            $this->limpiar($ventaGanadoraId, $producto->id, $categoria->id, $vendedor->id);

            foreach ([$script, $marcador, $marcadorFin, $marcadorError] as $archivo) {
                if (is_file($archivo)) {
                    @unlink($archivo);
                }
            }
        }
    }

    /**
     * Script que corre en el proceso secundario: confirma la venta ganadora,
     * avisa que ya tiene el lock y recién después commitea.
     */
    private function scriptSecundario(
        int $productoId,
        int $vendedorId,
        string $operacionId,
        string $marcador,
        string $marcadorFin,
        string $marcadorError
    ): string {
        $plantilla = <<<'PHP'
<?php

declare(strict_types=1);

// Proceso secundario del test de concurrencia. Se borra al terminar el test.
$env = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'pgsql',
    'DB_HOST' => {{HOST}},
    'DB_PORT' => {{PORT}},
    'DB_DATABASE' => {{DATABASE}},
    'DB_USERNAME' => {{USERNAME}},
    'DB_PASSWORD' => {{PASSWORD}},
];

foreach ($env as $clave => $valor) {
    $_ENV[$clave] = $valor;
    $_SERVER[$clave] = $valor;
    putenv($clave.'='.$valor);
}

require {{BASE}}.'/vendor/autoload.php';

$app = require {{BASE}}.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// VentaService audita con request()->ip(): se le da un request de consola.
$app->instance('request', Illuminate\Http\Request::create('/', 'POST', [], [], [], [
    'REMOTE_ADDR' => '127.0.0.1',
    'HTTP_USER_AGENT' => 'test-concurrencia-ventas',
]));

try {
    Illuminate\Support\Facades\DB::beginTransaction();

    $venta = $app->make(App\Services\VentaService::class)->confirmar(
        [['producto_id' => {{PRODUCTO}}, 'cantidad' => 1]],
        [['metodo' => 'Efectivo', 'monto' => 100]],
        [
            'cliente_id' => null,
            'vendedor_id' => {{VENDEDOR}},
            'operacion_id' => {{OPERACION}},
        ]
    );

    // El lock del producto sigue tomado hasta el commit de esta transacción.
    file_put_contents(
        {{MARCADOR}},
        Illuminate\Support\Facades\DB::connection()->getDatabaseName().'|'.$venta->id
    );

    usleep({{SLEEP}});

    Illuminate\Support\Facades\DB::commit();

    file_put_contents({{FINAL}}, 'ok');
} catch (Throwable $e) {
    try {
        Illuminate\Support\Facades\DB::rollBack();
    } catch (Throwable) {
        // La transacción ya pudo haber abortado.
    }

    file_put_contents({{ERROR}}, get_class($e).': '.$e->getMessage());
    exit(1);
}
PHP;

        $config = config('database.connections.pgsql');

        return str_replace(
            [
                '{{HOST}}', '{{PORT}}', '{{DATABASE}}', '{{USERNAME}}', '{{PASSWORD}}',
                '{{BASE}}', '{{PRODUCTO}}', '{{VENDEDOR}}', '{{OPERACION}}',
                '{{MARCADOR}}', '{{FINAL}}', '{{ERROR}}', '{{SLEEP}}',
            ],
            [
                var_export((string) $config['host'], true),
                var_export((string) $config['port'], true),
                var_export((string) $config['database'], true),
                var_export((string) $config['username'], true),
                var_export((string) $config['password'], true),
                var_export(base_path(), true),
                (string) $productoId,
                (string) $vendedorId,
                var_export($operacionId, true),
                var_export($marcador, true),
                var_export($marcadorFin, true),
                var_export($marcadorError, true),
                '800000',
            ],
            $plantilla
        );
    }

    /**
     * @return array<string, string>
     */
    private function envPostgres(): array
    {
        $config = config('database.connections.pgsql');

        return [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_HOST' => (string) $config['host'],
            'DB_PORT' => (string) $config['port'],
            'DB_DATABASE' => (string) $config['database'],
            'DB_USERNAME' => (string) $config['username'],
            'DB_PASSWORD' => (string) $config['password'],
        ];
    }

    private function esperarMarcador(string $ruta, Process $proceso, float $segundos): ?string
    {
        $limite = microtime(true) + $segundos;

        while (microtime(true) < $limite) {
            if (is_file($ruta)) {
                return (string) file_get_contents($ruta);
            }

            if (! $proceso->isRunning()) {
                break;
            }

            usleep(20000);
        }

        return is_file($ruta) ? (string) file_get_contents($ruta) : null;
    }

    private function limpiar(?int $ventaId, int $productoId, int $categoriaId, int $userId): void
    {
        $ventas = array_values(array_filter([$ventaId]));

        // El orden importa: audit → hijos → venta → inventario → maestros.
        DB::table('logs_auditoria')->whereIn('modelo_id', $ventas)->delete();
        DB::table('ingresos')->whereIn('venta_id', $ventas)->delete();
        DB::table('pagos')->whereIn('venta_id', $ventas)->delete();
        DB::table('detalle_ventas')->whereIn('venta_id', $ventas)->delete();
        DB::table('ventas')->whereIn('id', $ventas)->delete();

        DB::table('alertas_stock')->where('producto_id', $productoId)->delete();
        DB::table('movimientos_stock')->where('producto_id', $productoId)->delete();
        DB::table('productos')->where('id', $productoId)->delete();
        DB::table('categorias')->where('id', $categoriaId)->delete();
        DB::table('logs_auditoria')->where('user_id', $userId)->delete();
        DB::table('users')->where('id', $userId)->delete();
    }
}
