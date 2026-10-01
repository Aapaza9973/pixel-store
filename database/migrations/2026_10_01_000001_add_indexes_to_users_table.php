<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de rendimiento para la tabla `users` (tarea 1.1.2).
 *
 * Decisiones de diseño:
 * - Se omite `idx_users_email` a propósito: `email` ya es UNIQUE
 *   (`users_email_key`) y el btree de un UNIQUE cubre las búsquedas por
 *   igualdad (login). Un segundo índice sobre la misma columna sería
 *   redundante y solo agregaría coste de escritura y espacio.
 * - La migración es idempotente: la estructura de esta base de datos
 *   proviene del schema dump (`database/schema/pgsql-schema.sql`), que ya
 *   declara `idx_users_activo`. Si el índice ya existe, `up()` es no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $existentes = $this->indicesExistentes();

        Schema::table('users', function (Blueprint $table) use ($existentes): void {
            if (! in_array('idx_users_activo', $existentes, true)) {
                $table->index('activo', 'idx_users_activo');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $existentes = $this->indicesExistentes();

        Schema::table('users', function (Blueprint $table) use ($existentes): void {
            if (in_array('idx_users_activo', $existentes, true)) {
                $table->dropIndex('idx_users_activo');
            }
        });
    }

    /**
     * Nombres de los índices existentes en la tabla `users` (PostgreSQL).
     *
     * @return array<int, string>
     */
    private function indicesExistentes(): array
    {
        return collect(
            DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'users'")
        )->pluck('indexname')->all();
    }
};
