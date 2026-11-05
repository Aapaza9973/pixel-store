<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'ocultar_si_sin_stock')) {
                $table->boolean('ocultar_si_sin_stock')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'ocultar_si_sin_stock')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('ocultar_si_sin_stock');
            });
        }
    }
};
