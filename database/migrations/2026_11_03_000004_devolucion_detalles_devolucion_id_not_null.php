<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Una línea de devolución pertenece siempre a una cabecera. Antes
 * devolucion_id era nullable con ON DELETE SET NULL: borrar la devolución
 * dejaba líneas huérfanas apuntando a una venta, y el CASCADE de
 * devolucion_detalles.venta_id podía llevárselas con la venta, rompiendo la
 * trazabilidad. Tablas vacías: no hay migración de datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        $huerfanas = DB::table('devolucion_detalles')->whereNull('devolucion_id')->count();

        if ($huerfanas > 0) {
            throw new RuntimeException(
                'Hay líneas con devolucion_id null: migrar a mano antes de aplicar esta migración.'
            );
        }

        DB::statement('ALTER TABLE devolucion_detalles DROP CONSTRAINT IF EXISTS devolucion_detalles_devolucion_id_foreign');
        DB::statement('ALTER TABLE devolucion_detalles ALTER COLUMN devolucion_id SET NOT NULL');
        DB::statement(
            'ALTER TABLE devolucion_detalles ADD CONSTRAINT devolucion_detalles_devolucion_id_foreign
             FOREIGN KEY (devolucion_id) REFERENCES devoluciones(id) ON DELETE CASCADE'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE devolucion_detalles DROP CONSTRAINT IF EXISTS devolucion_detalles_devolucion_id_foreign');
        DB::statement('ALTER TABLE devolucion_detalles ALTER COLUMN devolucion_id DROP NOT NULL');
        DB::statement(
            'ALTER TABLE devolucion_detalles ADD CONSTRAINT devolucion_detalles_devolucion_id_foreign
             FOREIGN KEY (devolucion_id) REFERENCES devoluciones(id) ON DELETE SET NULL'
        );
    }
};
