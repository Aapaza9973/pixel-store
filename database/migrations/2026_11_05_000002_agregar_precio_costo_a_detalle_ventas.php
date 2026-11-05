<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            if (! Schema::hasColumn('detalle_ventas', 'precio_costo')) {
                $table->decimal('precio_costo', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('detalle_ventas', 'precio_costo')) {
            Schema::table('detalle_ventas', function (Blueprint $table) {
                $table->dropColumn('precio_costo');
            });
        }
    }
};
