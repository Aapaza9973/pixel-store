<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un pedido web lo paga el comprador, no un usuario del sistema: el ingreso
 * debe poder quedar sin `user_id` (antes la columna era NOT NULL y rompía el
 * cobro online de invitados).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ingresos')) {
            return;
        }

        DB::statement('ALTER TABLE ingresos ALTER COLUMN user_id DROP NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('ingresos')) {
            return;
        }

        // Solo se puede volver atrás si no hay ingresos sin usuario.
        DB::statement('ALTER TABLE ingresos ALTER COLUMN user_id SET NOT NULL');
    }
};
