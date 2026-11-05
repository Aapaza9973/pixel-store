<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Órdenes de compra — esquema del Día 2 (3.4.1-3.4.3).
 *
 * `ordenes_compra` y `orden_compra_items` ya existen en el schema canónico
 * (database/schema/pgsql-schema.sql), así que esta migración las reconcilia
 * en vez de duplicarlas: el estado pasa de enum a varchar(20) con CHECK en
 * minúsculas (los cinco estados del flujo), fecha_emision queda obligatoria y
 * se agregan los índices de consulta. `orden_compra_recepcion` sí es nueva.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ordenes_compra')) {
            // fecha_emision nació nullable en add_fechas_to_ordenes_compra_table.
            DB::statement(
                'UPDATE ordenes_compra
                 SET fecha_emision = DATE(fecha)
                 WHERE fecha_emision IS NULL'
            );
            DB::statement('ALTER TABLE ordenes_compra ALTER COLUMN fecha_emision SET NOT NULL');

            // enum_orden_compra_estado -> varchar(20) con los cinco estados del
            // flujo de compras. 'Recibida' del schema original equivale a total.
            DB::statement('ALTER TABLE ordenes_compra ALTER COLUMN estado DROP DEFAULT');
            DB::statement(
                "ALTER TABLE ordenes_compra
                 ALTER COLUMN estado TYPE varchar(20)
                 USING (CASE lower(estado::text)
                          WHEN 'recibida' THEN 'recibida_total'
                          ELSE lower(estado::text)
                        END)"
            );
            DB::statement("ALTER TABLE ordenes_compra ALTER COLUMN estado SET DEFAULT 'borrador'");
            DB::statement('ALTER TABLE ordenes_compra DROP CONSTRAINT IF EXISTS ordenes_compra_estado_check');
            DB::statement(
                "ALTER TABLE ordenes_compra ADD CONSTRAINT ordenes_compra_estado_check
                 CHECK (estado IN ('borrador','enviada','recibida_parcial','recibida_total','cancelada'))"
            );

            DB::statement(
                'CREATE INDEX IF NOT EXISTS ordenes_compra_proveedor_estado_fecha_index
                 ON ordenes_compra (proveedor_id, estado, fecha_emision)'
            );
        }

        if (Schema::hasTable('orden_compra_items')) {
            DB::statement(
                'CREATE INDEX IF NOT EXISTS orden_compra_items_orden_producto_index
                 ON orden_compra_items (orden_compra_id, producto_id)'
            );
        }

        if (! Schema::hasTable('orden_compra_recepcion')) {
            Schema::create('orden_compra_recepcion', function (Blueprint $table) {
                $table->id();
                $table->foreignId('orden_id')->constrained('ordenes_compra')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->timestamp('fecha_recepcion');
                $table->text('notas')->nullable();
                $table->timestamps();

                $table->index('orden_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_recepcion');

        DB::statement('DROP INDEX IF EXISTS orden_compra_items_orden_producto_index');

        if (Schema::hasTable('ordenes_compra')) {
            DB::statement('DROP INDEX IF EXISTS ordenes_compra_proveedor_estado_fecha_index');

            DB::statement('ALTER TABLE ordenes_compra ALTER COLUMN fecha_emision DROP NOT NULL');
            DB::statement('ALTER TABLE ordenes_compra DROP CONSTRAINT IF EXISTS ordenes_compra_estado_check');
            DB::statement('ALTER TABLE ordenes_compra ALTER COLUMN estado DROP DEFAULT');
            DB::statement(
                "ALTER TABLE ordenes_compra
                 ALTER COLUMN estado TYPE enum_orden_compra_estado
                 USING (CASE estado
                          WHEN 'borrador' THEN 'Borrador'
                          WHEN 'enviada' THEN 'Enviada'
                          WHEN 'cancelada' THEN 'Cancelada'
                          ELSE 'Recibida'
                        END)::enum_orden_compra_estado"
            );
            DB::statement("ALTER TABLE ordenes_compra ALTER COLUMN estado SET DEFAULT 'Borrador'");
        }
    }
};
