<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 5.13.9: ingreso económico generado por una venta.
 *
 * La venta no se puede borrar mientras tenga un ingreso asociado
 * (restrictOnDelete): el asiento económico es parte del historial contable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('monto', 12, 2);
            $table->timestamp('fecha')->useCurrent();
            $table->string('tipo', 20)->default('venta');
            $table->timestamps();

            // Postgres no indexa las FK automáticamente.
            $table->index('venta_id');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
