<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Día 1 (5.11.1-5.11.4): autorizaciones de descuento por administrador.
 *
 * La tabla existía desde `2026_10_25_000008` como registro histórico atado a
 * una venta ya confirmada. El alcance 5.11 la convierte en una autorización
 * PREVIA a la venta, ligada por `operacion_id` y enlazada a la venta recién al
 * confirmar (5.11.5). Cambios:
 *
 *  - `operacion_id` UUID: misma clave de idempotencia de la venta.
 *  - `vendedor_id`: quién solicita la autorización (`admin_id` es quién la da).
 *  - `descuento_porcentaje` DECIMAL(5,2).
 *  - `monto` -> `monto_descuento` DECIMAL(10,2) y `motivo` TEXT.
 *  - `venta_id` pasa a NULLABLE: todavía no hay venta cuando se autoriza.
 *  - UNIQUE parcial (operacion_id) WHERE venta_id IS NULL: una operación no
 *    puede tener dos autorizaciones pendientes.
 *
 * La tabla está vacía en las bases del repo (verificado), por eso las columnas
 * nuevas pueden ser NOT NULL sin backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        // monto -> monto_descuento (DECIMAL(10,2)).
        if (Schema::hasColumn('autorizaciones_descuento', 'monto')
            && ! Schema::hasColumn('autorizaciones_descuento', 'monto_descuento')) {
            Schema::table('autorizaciones_descuento', function (Blueprint $table) {
                $table->renameColumn('monto', 'monto_descuento');
            });
        }

        DB::statement('ALTER TABLE autorizaciones_descuento ALTER COLUMN monto_descuento TYPE DECIMAL(10,2)');

        // La venta se enlaza al confirmar, así que todavía no existe.
        DB::statement('ALTER TABLE autorizaciones_descuento ALTER COLUMN venta_id DROP NOT NULL');

        // El motivo puede ser más largo que el varchar(255) original.
        DB::statement('ALTER TABLE autorizaciones_descuento ALTER COLUMN motivo TYPE TEXT');

        Schema::table('autorizaciones_descuento', function (Blueprint $table) {
            if (! Schema::hasColumn('autorizaciones_descuento', 'operacion_id')) {
                $table->uuid('operacion_id')->after('id');
            }

            if (! Schema::hasColumn('autorizaciones_descuento', 'vendedor_id')) {
                $table->foreignId('vendedor_id')
                    ->after('admin_id')
                    ->constrained('users')
                    ->restrictOnDelete();
            }

            if (! Schema::hasColumn('autorizaciones_descuento', 'descuento_porcentaje')) {
                $table->decimal('descuento_porcentaje', 5, 2)->after('monto_descuento');
            }
        });

        DB::statement('CREATE INDEX IF NOT EXISTS autorizaciones_descuento_operacion_id_index ON autorizaciones_descuento (operacion_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS autorizaciones_descuento_admin_id_index ON autorizaciones_descuento (admin_id)');

        // Una operación no puede tener dos autorizaciones pendientes. El índice
        // parcial deja convivir las autorizaciones ya enlazadas a una venta.
        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS autorizaciones_descuento_operacion_pendiente_unique
             ON autorizaciones_descuento (operacion_id) WHERE venta_id IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS autorizaciones_descuento_operacion_pendiente_unique');
        DB::statement('DROP INDEX IF EXISTS autorizaciones_descuento_admin_id_index');
        DB::statement('DROP INDEX IF EXISTS autorizaciones_descuento_operacion_id_index');

        Schema::table('autorizaciones_descuento', function (Blueprint $table) {
            if (Schema::hasColumn('autorizaciones_descuento', 'descuento_porcentaje')) {
                $table->dropColumn('descuento_porcentaje');
            }

            if (Schema::hasColumn('autorizaciones_descuento', 'vendedor_id')) {
                $table->dropConstrainedForeignId('vendedor_id');
            }

            if (Schema::hasColumn('autorizaciones_descuento', 'operacion_id')) {
                $table->dropColumn('operacion_id');
            }
        });

        DB::statement('ALTER TABLE autorizaciones_descuento ALTER COLUMN motivo TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE autorizaciones_descuento ALTER COLUMN venta_id SET NOT NULL');

        if (Schema::hasColumn('autorizaciones_descuento', 'monto_descuento')
            && ! Schema::hasColumn('autorizaciones_descuento', 'monto')) {
            DB::statement('ALTER TABLE autorizaciones_descuento ALTER COLUMN monto_descuento TYPE DECIMAL(12,2)');

            Schema::table('autorizaciones_descuento', function (Blueprint $table) {
                $table->renameColumn('monto_descuento', 'monto');
            });
        }
    }
};
