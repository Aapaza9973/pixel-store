<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HU-3.5: encuesta anónima de 3 preguntas (escala 1-5) con comentario libre.
 * No guarda IP ni usuario; `venta_id` es opcional y solo para trazabilidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('encuestas')) {
            return;
        }

        Schema::create('encuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->unsignedTinyInteger('atencion');
            $table->unsignedTinyInteger('catalogo');
            $table->unsignedTinyInteger('recomendacion');
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuestas');
    }
};
