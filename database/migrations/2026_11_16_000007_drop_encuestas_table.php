<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quita la encuesta anónima del sistema: tabla, permiso y asignaciones.
 * La migración que la creaba se eliminó junto con el resto del módulo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('encuestas');

        if (Schema::hasTable('permissions')) {
            $permisoId = DB::table('permissions')->where('name', 'ver encuestas')->value('id');

            if ($permisoId !== null) {
                if (Schema::hasTable('role_has_permissions')) {
                    DB::table('role_has_permissions')->where('permission_id', $permisoId)->delete();
                }

                if (Schema::hasTable('model_has_permissions')) {
                    DB::table('model_has_permissions')->where('permission_id', $permisoId)->delete();
                }

                DB::table('permissions')->where('id', $permisoId)->delete();
            }
        }
    }

    public function down(): void
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
};
