<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estado activo + unicidad de NIT/CI (ignorando NULL).
 * Sprint 2 — Día 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('clientes', 'activo')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->boolean('activo')->default(true);
            });
        }

        // UNIQUE parcial: PostgreSQL permite múltiples NULL, pero no duplicados reales.
        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS clientes_nit_ci_unique
             ON clientes (nit_ci) WHERE nit_ci IS NOT NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS clientes_nit_ci_unique');

        if (Schema::hasColumn('clientes', 'activo')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropColumn('activo');
            });
        }
    }
};
