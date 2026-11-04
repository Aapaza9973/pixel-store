<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2.7.1: favoritos para usuarios autenticados y visitantes anónimos.
 *
 * Un favorito pertenece a un user_id o a un session_token (cookie del visitante).
 * Los índices únicos son parciales: cada uno aplica solo a su tipo de dueño.
 * El CHECK garantiza que al menos uno de los dos esté presente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favoritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_token', 64)->nullable()->index();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->timestamps();
        });

        DB::statement(
            'CREATE UNIQUE INDEX favoritos_user_producto_unique
             ON favoritos (user_id, producto_id) WHERE user_id IS NOT NULL'
        );
        DB::statement(
            'CREATE UNIQUE INDEX favoritos_session_producto_unique
             ON favoritos (session_token, producto_id) WHERE session_token IS NOT NULL'
        );
        DB::statement(
            'ALTER TABLE favoritos ADD CONSTRAINT favoritos_dueno_check
             CHECK (user_id IS NOT NULL OR session_token IS NOT NULL)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('favoritos');
    }
};
