<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fechas de emisión y entrega de órdenes de compra.
 * Sprint 2 — Día 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ordenes_compra', 'fecha_emision')) {
            Schema::table('ordenes_compra', function (Blueprint $table) {
                $table->date('fecha_emision')->nullable();
            });
        }

        if (! Schema::hasColumn('ordenes_compra', 'fecha_entrega')) {
            Schema::table('ordenes_compra', function (Blueprint $table) {
                $table->date('fecha_entrega')->nullable();
            });
        }

        DB::statement(
            'UPDATE ordenes_compra
             SET fecha_emision = DATE(fecha)
             WHERE fecha_emision IS NULL AND fecha IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            foreach (['fecha_entrega', 'fecha_emision'] as $columna) {
                if (Schema::hasColumn('ordenes_compra', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
