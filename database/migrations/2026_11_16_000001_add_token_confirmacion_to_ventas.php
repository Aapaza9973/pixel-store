<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HU-3.3: token de 48 caracteres para que el comprador confirme o cancele su
 * pedido desde el correo, sin cuenta y sin poder enumerar pedidos ajenos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ventas', 'token_confirmacion')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->char('token_confirmacion', 48)->nullable()->unique();
            });
        }

        if (! Schema::hasColumn('ventas', 'confirmado_en')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->timestamp('confirmado_en')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ventas', 'confirmado_en')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropColumn('confirmado_en');
            });
        }

        if (Schema::hasColumn('ventas', 'token_confirmacion')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropColumn('token_confirmacion');
            });
        }
    }
};
