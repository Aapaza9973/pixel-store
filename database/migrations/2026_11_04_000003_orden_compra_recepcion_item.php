<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Detalle de cada recepción de orden de compra (3.4.7, Día 3).
 *
 * `orden_compra_recepcion` solo guarda el hecho (quién y cuándo). Sin esta
 * tabla no hay forma de saber qué línea y cuántas unidades llegaron en cada
 * recepción, y sin eso no se puede controlar lo pendiente de recibir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orden_compra_recepcion_items')) {
            return;
        }

        Schema::create('orden_compra_recepcion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')
                ->constrained('orden_compra_recepcion')
                ->cascadeOnDelete();
            $table->foreignId('orden_compra_item_id')
                ->constrained('orden_compra_items')
                ->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->timestamps();

            $table->index(['recepcion_id', 'orden_compra_item_id']);
        });

        DB::statement(
            'ALTER TABLE orden_compra_recepcion_items
             ADD CONSTRAINT orden_compra_recepcion_items_cantidad_check
             CHECK (cantidad > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_recepcion_items');
    }
};
