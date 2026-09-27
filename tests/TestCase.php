<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use PDO;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->app->environment('testing')) {
            $this->cargarSchemaSiEstaVacio();
        }
    }

    /**
     * Carga database/schema/pgsql-schema.sql con un PDO independiente.
     *
     * Se usa una conexión paralela para que la carga quede confirmada y no
     * sea revertida por la transacción que abre DatabaseTransactions.
     */
    private function cargarSchemaSiEstaVacio(): void
    {
        $tablas = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");

        if (count($tablas) > 0) {
            return;
        }

        $ruta = database_path('schema/pgsql-schema.sql');

        if (! is_file($ruta)) {
            return;
        }

        $config = config('database.connections.'.config('database.default'));

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config['host'],
            $config['port'],
            $config['database']
        );

        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->exec(file_get_contents($ruta));
    }
}
