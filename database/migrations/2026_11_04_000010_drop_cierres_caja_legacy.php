<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Día 5 (D5-C): descarta la tabla legada `cierres_caja_legacy`.
 *
 * El Día 4 reemplazó la caja por `cierres_caja` + `cierre_caja_pagos`
 * (commit 549c21c). En entornos donde la migración vieja RENOMBRÓ la tabla en
 * lugar de recrearla puede quedar `cierres_caja_legacy` con la estructura
 * antigua (`fecha_cierre`, totales por método). Acá se elimina.
 *
 * Idempotente: si la tabla no existe (dump base actual), no hace nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cierres_caja_legacy');
    }

    public function down(): void
    {
        // No se recrea: la estructura legada no es parte del schema canónico.
    }
};
