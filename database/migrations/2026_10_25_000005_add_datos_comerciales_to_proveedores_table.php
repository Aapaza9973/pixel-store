<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos comerciales del proveedor (HU-6.x compras).
 * Sprint 2 — Día 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            if (! Schema::hasColumn('proveedores', 'plazos_pago')) {
                $table->string('plazos_pago', 100)->nullable();
            }

            if (! Schema::hasColumn('proveedores', 'tiempo_entrega')) {
                $table->string('tiempo_entrega', 100)->nullable();
            }

            if (! Schema::hasColumn('proveedores', 'descuento_habitual')) {
                $table->decimal('descuento_habitual', 5, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            foreach (['plazos_pago', 'tiempo_entrega', 'descuento_habitual'] as $columna) {
                if (Schema::hasColumn('proveedores', $columna)) {
                    $table->dropColumn($columna);
                }
            }
        });
    }
};
