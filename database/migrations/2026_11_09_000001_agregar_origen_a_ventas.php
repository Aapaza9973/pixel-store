<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (! Schema::hasColumn('ventas', 'origen')) {
                $table->string('origen', 10)->default('pos');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('ventas', 'origen')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropColumn('origen');
            });
        }
    }
};
