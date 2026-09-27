<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la estructura física (pasillo, estante, anaquel) a ubicaciones.
     * Idempotente: no falla si el schema dump ya trae las columnas.
     */
    public function up(): void
    {
        $columnas = [
            'pasillo' => 'direccion',
            'estante' => 'pasillo',
            'anaquel' => 'estante',
        ];

        foreach ($columnas as $columna => $despuesDe) {
            if (Schema::hasColumn('ubicaciones', $columna)) {
                continue;
            }

            Schema::table('ubicaciones', function (Blueprint $table) use ($columna, $despuesDe) {
                $table->string($columna, 50)->nullable()->after($despuesDe);
            });
        }
    }

    public function down(): void
    {
        foreach (['pasillo', 'estante', 'anaquel'] as $columna) {
            if (Schema::hasColumn('ubicaciones', $columna)) {
                Schema::table('ubicaciones', function (Blueprint $table) use ($columna) {
                    $table->dropColumn($columna);
                });
            }
        }
    }
};
