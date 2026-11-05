<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * 5.13.12: concurrencia REAL de 100 procesos sobre el último ítem en stock.
 *
 * Cada proceso es un PHP independiente (conexión PDO propia) que bootstrapea
 * la app y llama a VentaService::confirmar con una operación distinta. Los
 * 100 se lanzan sin esperar y esperan una barrera de tiempo común: así compiten
 * a la vez en vez de arrancar escalonados por el costo de `proc_open`.
 *
 * Un jitter corto (0-300 ms) después de la barrera reparte las conexiones para
 * no agotar `max_connections` de Postgres; el lock `FOR UPDATE` del producto
 * sigue siendo el punto de serialización.
 *
 * No usa DatabaseTransactions: los hijos solo ven filas commiteadas. La
 * limpieza va en finally y borra todo lo que dejó la corrida.
 */
class Venta100ConcurrenciaTest extends TestCase
{
    private const PROCESOS = 100;

    /** Segundos que se les da a los 100 procesos para bootstrapear. */
    private const BARRERA = 5.0;

    public function test_cien_ventas_concurrentes_del_ultimo_item_solo_una_gana(): void
    {
        $vendedor = User::factory()->create(['activo' => true]);

        $categoria = Categoria::create([
            'nombre' => 'Categoría Concurrencia 100 '.Str::random(8),
            'tipo' => 'Componente',
        ]);

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'GPU Última Unidad 100 Procesos',
            'precio_unitario' => 100,
            'stock' => 1,
            'umbral_alerta' => 1,
        ]);

        $directorio = storage_path('framework/testing');

        if (! is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $operaciones = [];
        $scripts = [];
        $procesos = [];

        try {
            $barrera = microtime(true) + self::BARRERA;

            for ($i = 0; $i < self::PROCESOS; $i++) {
                $operacion = (string) Str::uuid();
                $operaciones[] = $operacion;

                $script = $directorio.'/venta_100_'.$i.'.php';
                file_put_contents($script, $this->scriptSecundario(
                    $producto->id,
                    $vendedor->id,
                    $operacion,
                    $barrera,
                    random_int(0, 300000)
                ));

                $scripts[] = $script;
            }

            // La conexión del test no debe competir por un slot con los hijos.
            DB::disconnect();

            foreach ($scripts as $i => $script) {
                $proceso = new Process([PHP_BINARY, $script], base_path());
                $proceso->setTimeout(120);
                $proceso->start();
                $procesos[$i] = $proceso;
            }

            $ganadores = 0;
            $perdedores = 0;
            $inesperados = [];

            foreach ($procesos as $i => $proceso) {
                $proceso->wait();
                $codigo = $proceso->getExitCode();

                if ($codigo === 0) {
                    $ganadores++;
                } elseif ($codigo === 1) {
                    $perdedores++;
                } else {
                    $inesperados[$i] = trim($proceso->getErrorOutput())
                        ?: trim($proceso->getOutput())
                        ?: 'exit code '.$codigo;
                }
            }

            $this->assertSame([], $inesperados, 'Procesos con errores inesperados: '.json_encode($inesperados));
            $this->assertGreaterThanOrEqual(1, $ganadores, 'Debe ganar al menos un proceso.');
            $this->assertLessThanOrEqual(2, $ganadores, 'No puede haber más de 2 ganadores.');
            $this->assertSame(self::PROCESOS - $ganadores, $perdedores);
            $this->assertGreaterThanOrEqual(98, $perdedores);

            // No hubo oversell: el stock queda en 0 y hay exactamente una venta
            // por operación ganadora.
            $this->assertSame(0, (int) $producto->fresh()->stock);
            $this->assertSame($ganadores, Venta::whereIn('operacion_id', $operaciones)->count());

            $perdedoras = array_values(array_diff($operaciones, Venta::whereIn('operacion_id', $operaciones)->pluck('operacion_id')->all()));
            $this->assertCount($perdedores, $perdedoras);
            $this->assertSame(0, Venta::whereIn('operacion_id', $perdedoras)->count(), 'Una operación perdedora dejó una venta.');
        } finally {
            foreach ($procesos as $proceso) {
                if ($proceso->isRunning()) {
                    $proceso->stop(1);
                }
            }

            $this->limpiar($operaciones, $producto->id, $categoria->id, $vendedor->id);

            foreach (array_merge($scripts, glob($directorio.'/venta_100_*.php') ?: []) as $archivo) {
                if (is_file($archivo)) {
                    @unlink($archivo);
                }
            }
        }
    }

    /**
     * Script que corre en cada proceso: espera la barrera, agrega su jitter y
     * compite por el último ítem. 0 = ganó, 1 = sin stock, 2 = error inesperado.
     */
    private function scriptSecundario(
        int $productoId,
        int $vendedorId,
        string $operacionId,
        float $barrera,
        int $jitter
    ): string {
        $plantilla = <<<'PHP'
<?php

declare(strict_types=1);

// Proceso secundario del test de 100 ventas concurrentes. Se borra al terminar.
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
    'HTTP_USER_AGENT' => 'test-100-concurrencia',
]));

// Barrera común: todos compiten a la vez, no cuando termina su boot.
$barrera = (float) {{BARRERA}};

while (microtime(true) < $barrera) {
    usleep(2000);
}

if ({{JITTER}} > 0) {
    usleep({{JITTER}});
}

try {
    $app->make(App\Services\VentaService::class)->confirmar(
        [['producto_id' => {{PRODUCTO}}, 'cantidad' => 1]],
        [['metodo' => 'Efectivo', 'monto' => 100]],
        [
            'cliente_id' => null,
            'vendedor_id' => {{VENDEDOR}},
            'operacion_id' => {{OPERACION}},
        ]
    );

    exit(0);
} catch (App\Exceptions\StockInsuficienteException $e) {
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e).': '.$e->getMessage().PHP_EOL);
    exit(2);
}
PHP;

        $config = config('database.connections.pgsql');

        return str_replace(
            [
                '{{HOST}}', '{{PORT}}', '{{DATABASE}}', '{{USERNAME}}', '{{PASSWORD}}',
                '{{BASE}}', '{{PRODUCTO}}', '{{VENDEDOR}}', '{{OPERACION}}',
                '{{BARRERA}}', '{{JITTER}}',
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
                var_export((string) $barrera, true),
                (string) $jitter,
            ],
            $plantilla
        );
    }

    /**
     * @param  array<int, string>  $operaciones
     */
    private function limpiar(array $operaciones, int $productoId, int $categoriaId, int $userId): void
    {
        $ventas = DB::table('ventas')->whereIn('operacion_id', $operaciones)->pluck('id')->all();

        if ($ventas !== []) {
            DB::table('logs_auditoria')
                ->where('modelo', Venta::class)
                ->whereIn('modelo_id', $ventas)
                ->delete();
            DB::table('ingresos')->whereIn('venta_id', $ventas)->delete();
            DB::table('pagos')->whereIn('venta_id', $ventas)->delete();
            DB::table('detalle_ventas')->whereIn('venta_id', $ventas)->delete();
            DB::table('ventas')->whereIn('id', $ventas)->delete();
        }

        DB::table('alertas_stock')->where('producto_id', $productoId)->delete();
        DB::table('movimientos_stock')->where('producto_id', $productoId)->delete();
        DB::table('stock_ubicacion')->where('producto_id', $productoId)->delete();
        DB::table('productos')->where('id', $productoId)->delete();
        DB::table('categorias')->where('id', $categoriaId)->delete();

        DB::table('logs_auditoria')->where('user_id', $userId)->delete();
        DB::table('password_histories')->where('user_id', $userId)->delete();
        DB::table('users')->where('id', $userId)->delete();
    }
}
