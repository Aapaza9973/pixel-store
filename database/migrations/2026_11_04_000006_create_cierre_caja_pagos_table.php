<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 6.1.2: desglose por método de pago de un cierre de caja.
 *
 * `metodo` usa el enum nativo `enum_pago_metodo` para que no pueda divergir de
 * `pagos.metodo`. Se crea como varchar y se convierte, porque Blueprint no
 * crea tipos enum de Postgres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierre_caja_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cierre_caja_id')->constrained('cierres_caja')->cascadeOnDelete();
            $table->string('metodo', 20);
            $table->decimal('monto_sistema', 12, 2)->default(0);
            $table->decimal('monto_real', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['cierre_caja_id', 'metodo']);
        });

        DB::statement(
            'ALTER TABLE cierre_caja_pagos
             ALTER COLUMN metodo TYPE enum_pago_metodo
             USING metodo::enum_pago_metodo'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cierre_caja_pagos');
    }
};
