<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Galería de producto y autorizaciones de descuento.
 * Sprint 2 — Día 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('producto_imagenes')) {
            Schema::create('producto_imagenes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
                $table->string('path', 500);
                $table->string('alt', 255)->nullable();
                $table->unsignedSmallInteger('orden')->default(0);
                $table->boolean('es_principal')->default(false);
                $table->timestamps();

                $table->index(['producto_id', 'orden']);
            });

            // Solo una imagen principal por producto.
            DB::statement(
                'CREATE UNIQUE INDEX IF NOT EXISTS producto_imagenes_principal_unique
                 ON producto_imagenes (producto_id) WHERE es_principal = true'
            );
        }

        if (! Schema::hasTable('autorizaciones_descuento')) {
            Schema::create('autorizaciones_descuento', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
                $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
                $table->decimal('monto', 12, 2);
                $table->string('motivo', 255)->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamps();

                $table->index('venta_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('autorizaciones_descuento');

        DB::statement('DROP INDEX IF EXISTS producto_imagenes_principal_unique');
        Schema::dropIfExists('producto_imagenes');
    }
};
