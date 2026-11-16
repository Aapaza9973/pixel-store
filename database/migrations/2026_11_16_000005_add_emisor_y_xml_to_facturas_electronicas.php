<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía la tabla `facturas_electronicas` del schema dump con los dos datos que
 * necesita la emisión interna: usuario emisor y XML generado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facturas_electronicas')) {
            return;
        }

        if (! Schema::hasColumn('facturas_electronicas', 'user_id')) {
            Schema::table('facturas_electronicas', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('facturas_electronicas', 'xml')) {
            Schema::table('facturas_electronicas', function (Blueprint $table) {
                $table->longText('xml')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('facturas_electronicas')) {
            return;
        }

        if (Schema::hasColumn('facturas_electronicas', 'xml')) {
            Schema::table('facturas_electronicas', function (Blueprint $table) {
                $table->dropColumn('xml');
            });
        }

        if (Schema::hasColumn('facturas_electronicas', 'user_id')) {
            Schema::table('facturas_electronicas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
