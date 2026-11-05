<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * R-VD-RF-06 (devoluciones): cabecera idempotente + líneas.
 *
 * La tabla original `devoluciones` era por línea (producto_id + cantidad) y estaba
 * vacía (verificado: 0 filas, sin modelo ni controlador). Se renombra a
 * `devolucion_detalles` y se crea la cabecera nueva.
 *
 * Sprint 2 — Día 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Renombrar la tabla de líneas solo si aún no se hizo.
        if (Schema::hasTable('devoluciones') && ! Schema::hasColumn('devoluciones', 'operacion_id')) {
            Schema::rename('devoluciones', 'devolucion_detalles');
        }

        // 2) Cabecera nueva (debe existir antes de la FK de las líneas).
        if (! Schema::hasTable('devoluciones')) {
            Schema::create('devoluciones', function (Blueprint $table) {
                $table->id();
                $table->uuid('operacion_id')->unique();
                $table->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->text('motivo');
                $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
                $table->decimal('total_devuelto', 12, 2)->default(0);
                $table->timestamps();

                $table->index('estado');
            });
        }

        // 3) Enlazar las líneas con su cabecera.
        if (! Schema::hasColumn('devolucion_detalles', 'devolucion_id')) {
            Schema::table('devolucion_detalles', function (Blueprint $table) {
                $table->foreignId('devolucion_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('devoluciones')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('devolucion_detalles', 'devolucion_id')) {
            Schema::table('devolucion_detalles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('devolucion_id');
            });
        }

        Schema::dropIfExists('devoluciones');

        if (Schema::hasTable('devolucion_detalles') && ! Schema::hasTable('devoluciones')) {
            Schema::rename('devolucion_detalles', 'devoluciones');
        }
    }
};
