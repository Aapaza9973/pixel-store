<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Día 1: se había dejado ON DELETE CASCADE en devoluciones.venta_id.
 *
 * Se revierte a RESTRICT: borrar una venta no debe arrastrar sus devoluciones
 * (trazabilidad). Consistente con devolucion_detalles.venta_detalle_id.
 *
 * La migración 2026_11_03_000001 queda como paso histórico; ésta la corrige.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE devoluciones DROP CONSTRAINT IF EXISTS devoluciones_venta_id_foreign');
        DB::statement(
            'ALTER TABLE devoluciones ADD CONSTRAINT devoluciones_venta_id_foreign
             FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE RESTRICT'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE devoluciones DROP CONSTRAINT IF EXISTS devoluciones_venta_id_foreign');
        DB::statement(
            'ALTER TABLE devoluciones ADD CONSTRAINT devoluciones_venta_id_foreign
             FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE'
        );
    }
};
