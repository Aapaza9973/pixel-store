<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * R-VD-RF-06: idempotencia de la confirmación de venta.
 * Sprint 2 — Día 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ventas', 'operacion_id')) {
            return;
        }

        Schema::table('ventas', function (Blueprint $table) {
            $table->uuid('operacion_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ventas', 'operacion_id')) {
            return;
        }

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique(['operacion_id']);
            $table->dropColumn('operacion_id');
        });
    }
};
