<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Límite de descuento autorizado por rol (HU-5.x).
 * Sprint 2 — Día 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('roles', 'descuento_maximo')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->decimal('descuento_maximo', 5, 2)->default(0);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('roles', 'descuento_maximo')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('descuento_maximo');
        });
    }
};
