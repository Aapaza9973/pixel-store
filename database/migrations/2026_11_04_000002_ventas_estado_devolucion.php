<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 5.15.3: agrega los estados de devolución al enum de ventas.
 *
 * `ventas.estado` es un tipo enum de Postgres (enum_venta_estado), no varchar.
 * ADD VALUE no puede ejecutarse dentro de la misma transacción en la que se usa
 * el valor nuevo, por eso la migración corre fuera de transacción.
 *
 * 'Pagado' se mantiene: Venta::estaPagada() depende de ese literal.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement("ALTER TYPE enum_venta_estado ADD VALUE IF NOT EXISTS 'parcialmente_devuelta'");
        DB::statement("ALTER TYPE enum_venta_estado ADD VALUE IF NOT EXISTS 'totalmente_devuelta'");
    }

    /**
     * Postgres no permite quitar valores de un enum sin recrearlo. Se revierte
     * solo si ninguna venta usa los estados nuevos.
     */
    public function down(): void
    {
        $enUso = DB::table('ventas')
            ->whereIn('estado', ['parcialmente_devuelta', 'totalmente_devuelta'])
            ->exists();

        if ($enUso) {
            throw new RuntimeException(
                'No se puede revertir: hay ventas en parcialmente_devuelta o totalmente_devuelta.'
            );
        }

        DB::statement('ALTER TYPE enum_venta_estado RENAME TO enum_venta_estado_old');
        DB::statement("CREATE TYPE enum_venta_estado AS ENUM ('Pendiente', 'Pagado', 'Cancelada')");
        DB::statement('ALTER TABLE ventas ALTER COLUMN estado DROP DEFAULT');
        DB::statement(
            'ALTER TABLE ventas ALTER COLUMN estado TYPE enum_venta_estado
             USING estado::text::enum_venta_estado'
        );
        DB::statement("ALTER TABLE ventas ALTER COLUMN estado SET DEFAULT 'Pendiente'");
        DB::statement('DROP TYPE enum_venta_estado_old');
    }
};
