<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Día 1 (5.14.1-5.14.3): completa el schema de devoluciones.
 *
 * La cabecera `devoluciones` y sus líneas ya existían (migración
 * 2026_10_25_000007, que renombró la tabla vieja a `devolucion_detalles`). Esta
 * migración cierra las diferencias del alcance 5.14 sin recrear nada:
 *
 *  - ventas: borrar una venta cascadea sus devoluciones (antes RESTRICT).
 *  - estado: 'procesada' por defecto y 'anulada' como único otro estado.
 *    5.15 agrega los estados parcial/total sobre esta misma columna.
 *  - total_devuelto: DECIMAL(10,2).
 *  - líneas: `venta_detalle_id` -> detalle_ventas (RESTRICT: no se puede borrar
 *    una línea vendida si tiene devoluciones), `monto` e índices.
 *
 * `devolucion_detalles.estado` sigue con el enum viejo
 * (Pendiente/Aprobada/Rechazada) hasta 5.15, que define los estados
 * parcial/total y unifica el estado de las líneas en `devoluciones`.
 *
 * Es idempotente: todos los pasos están guardados por hasColumn/hasIndex o por
 * DROP ... IF EXISTS, así que puede correr sobre una base creada desde el dump
 * canónico o sobre una migrada.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- Cabecera: FK en cascada ---
        DB::statement('ALTER TABLE devoluciones DROP CONSTRAINT IF EXISTS devoluciones_venta_id_foreign');
        DB::statement(
            'ALTER TABLE devoluciones ADD CONSTRAINT devoluciones_venta_id_foreign
             FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE'
        );

        // --- Cabecera: estado procesada|anulada ---
        DB::statement("ALTER TABLE devoluciones ALTER COLUMN estado SET DEFAULT 'procesada'");
        DB::statement('ALTER TABLE devoluciones DROP CONSTRAINT IF EXISTS devoluciones_estado_check');
        DB::statement(
            "ALTER TABLE devoluciones ADD CONSTRAINT devoluciones_estado_check
             CHECK (estado IN ('procesada', 'anulada'))"
        );

        // --- Cabecera: total_devuelto DECIMAL(10,2) ---
        DB::statement('ALTER TABLE devoluciones ALTER COLUMN total_devuelto TYPE DECIMAL(10,2)');

        // --- Cabecera: índices de las FK (estado ya tenía el suyo) ---
        DB::statement('CREATE INDEX IF NOT EXISTS devoluciones_venta_id_index ON devoluciones (venta_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS devoluciones_user_id_index ON devoluciones (user_id)');

        // --- Líneas: vínculo con la línea vendida ---
        if (! Schema::hasColumn('devolucion_detalles', 'venta_detalle_id')) {
            Schema::table('devolucion_detalles', function (Blueprint $table) {
                $table->foreignId('venta_detalle_id')
                    ->nullable()
                    ->after('devolucion_id')
                    ->constrained('detalle_ventas')
                    ->restrictOnDelete();
            });
        }

        // --- Líneas: monto (antes monto_reembolso) DECIMAL(10,2) ---
        if (Schema::hasColumn('devolucion_detalles', 'monto_reembolso')
            && ! Schema::hasColumn('devolucion_detalles', 'monto')) {
            Schema::table('devolucion_detalles', function (Blueprint $table) {
                $table->renameColumn('monto_reembolso', 'monto');
            });
        }

        DB::statement('ALTER TABLE devolucion_detalles ALTER COLUMN monto TYPE DECIMAL(10,2)');

        // --- Líneas: índices ---
        DB::statement('CREATE INDEX IF NOT EXISTS devolucion_detalles_devolucion_id_index ON devolucion_detalles (devolucion_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS devolucion_detalles_venta_detalle_id_index ON devolucion_detalles (venta_detalle_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS devolucion_detalles_venta_detalle_id_index');
        DB::statement('DROP INDEX IF EXISTS devolucion_detalles_devolucion_id_index');
        DB::statement('DROP INDEX IF EXISTS devoluciones_user_id_index');
        DB::statement('DROP INDEX IF EXISTS devoluciones_venta_id_index');

        if (Schema::hasColumn('devolucion_detalles', 'monto')
            && ! Schema::hasColumn('devolucion_detalles', 'monto_reembolso')) {
            DB::statement('ALTER TABLE devolucion_detalles ALTER COLUMN monto TYPE DECIMAL(12,2)');

            Schema::table('devolucion_detalles', function (Blueprint $table) {
                $table->renameColumn('monto', 'monto_reembolso');
            });
        }

        if (Schema::hasColumn('devolucion_detalles', 'venta_detalle_id')) {
            Schema::table('devolucion_detalles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('venta_detalle_id');
            });
        }

        DB::statement('ALTER TABLE devoluciones ALTER COLUMN total_devuelto TYPE DECIMAL(12,2)');
        DB::statement('ALTER TABLE devoluciones DROP CONSTRAINT IF EXISTS devoluciones_estado_check');
        DB::statement(
            "ALTER TABLE devoluciones ADD CONSTRAINT devoluciones_estado_check
             CHECK (estado IN ('pendiente', 'aprobada', 'rechazada'))"
        );
        DB::statement("ALTER TABLE devoluciones ALTER COLUMN estado SET DEFAULT 'pendiente'");

        DB::statement('ALTER TABLE devoluciones DROP CONSTRAINT IF EXISTS devoluciones_venta_id_foreign');
        DB::statement(
            'ALTER TABLE devoluciones ADD CONSTRAINT devoluciones_venta_id_foreign
             FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE RESTRICT'
        );
    }
};
