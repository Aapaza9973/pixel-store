<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HU-2.4b: la devolución puede registrarse como solicitud (`pendiente`) y
 * aprobarse o rechazarse después. Se amplía el CHECK de estado y se guarda
 * quién y cuándo aprobó, más el motivo del rechazo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('devoluciones')) {
            return;
        }

        DB::statement('ALTER TABLE devoluciones DROP CONSTRAINT IF EXISTS devoluciones_estado_check');
        DB::statement(
            "ALTER TABLE devoluciones ADD CONSTRAINT devoluciones_estado_check
             CHECK (estado IN ('pendiente', 'procesada', 'anulada', 'rechazada'))"
        );

        if (! Schema::hasColumn('devoluciones', 'aprobada_por')) {
            Schema::table('devoluciones', function (Blueprint $table) {
                $table->foreignId('aprobada_por')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('devoluciones', 'aprobada_en')) {
            Schema::table('devoluciones', function (Blueprint $table) {
                $table->timestamp('aprobada_en')->nullable();
            });
        }

        if (! Schema::hasColumn('devoluciones', 'motivo_rechazo')) {
            Schema::table('devoluciones', function (Blueprint $table) {
                $table->string('motivo_rechazo', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('devoluciones')) {
            return;
        }

        foreach (['motivo_rechazo', 'aprobada_en'] as $columna) {
            if (Schema::hasColumn('devoluciones', $columna)) {
                Schema::table('devoluciones', function (Blueprint $table) use ($columna) {
                    $table->dropColumn($columna);
                });
            }
        }

        if (Schema::hasColumn('devoluciones', 'aprobada_por')) {
            Schema::table('devoluciones', function (Blueprint $table) {
                $table->dropConstrainedForeignId('aprobada_por');
            });
        }

        DB::statement('ALTER TABLE devoluciones DROP CONSTRAINT IF EXISTS devoluciones_estado_check');
        DB::statement(
            "ALTER TABLE devoluciones ADD CONSTRAINT devoluciones_estado_check
             CHECK (estado IN ('procesada', 'anulada'))"
        );
    }
};
