<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Un pedido del catálogo público no tiene vendedor: lo crea un invitado.
 * Sin esto, `ventas.user_id` (NOT NULL) obliga a inventar un usuario y
 * ensucia los reportes por vendedor.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ventas ALTER COLUMN user_id DROP NOT NULL');
    }

    public function down(): void
    {
        // Restaurar el NOT NULL solo es posible si no quedaron pedidos web.
        $hayVentasSinVendedor = DB::table('ventas')->whereNull('user_id')->exists();

        if (! $hayVentasSinVendedor) {
            DB::statement('ALTER TABLE ventas ALTER COLUMN user_id SET NOT NULL');
        }
    }
};
