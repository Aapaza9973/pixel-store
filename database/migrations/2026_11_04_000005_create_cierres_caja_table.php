<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 6.1.1: cabecera del cierre de caja por usuario, fecha y turno.
 *
 * El dump base traía una `cierres_caja` legada (`fecha_cierre` + un total por
 * método) que nunca se usó: no tenía filas ni referencias, y su forma no admite
 * el cierre por turno. Se descarta y se crea la estructura de la HU-6.1.
 *
 * `diferencia` se guarda (no se calcula al vuelo) porque es el dato que se
 * audita después: efectivo_real - efectivo_esperado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cierres_caja');

        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->string('turno', 20);
            $table->decimal('efectivo_esperado', 12, 2)->default(0);
            $table->decimal('efectivo_real', 12, 2)->default(0);
            $table->decimal('diferencia', 12, 2)->default(0);
            $table->text('notas')->nullable();
            $table->timestamps();

            // Un turno se cierra una sola vez por usuario.
            $table->unique(['user_id', 'fecha', 'turno']);
            $table->index(['fecha', 'user_id']);
        });

        // Blueprint no expresa CHECK: los turnos viven en un string acotado.
        DB::statement(
            "ALTER TABLE cierres_caja ADD CONSTRAINT cierres_caja_turno_check
             CHECK (turno IN ('mañana', 'tarde', 'noche'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_caja');
    }
};
