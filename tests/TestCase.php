<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Cargar el schema de PostgreSQL si la BD de test está vacía
        if ($this->app->environment('testing')) {
            try {
                DB::connection()->getPdo();
                $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                if (count($tables) === 0) {
                    Artisan::call('schema:load', ['file' => 'database/schema/pgsql-schema.sql']);
                }
            } catch (\Exception $e) {
                // BD vacía, cargar schema
                Artisan::call('schema:load', ['file' => 'database/schema/pgsql-schema.sql']);
            }
        }
    }
}

