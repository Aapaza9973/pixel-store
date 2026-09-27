<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta los seeders respetando el orden de dependencias:
     * permisos → catálogo → atributos → ubicaciones → productos.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CategoriaSeeder::class,
            MarcaSeeder::class,
            AtributoTecnicoSeeder::class,
            ValorAtributoSeeder::class,
            UbicacionSeeder::class,
            DemoProductoSeeder::class,
        ]);

        // Genera las alertas de los productos demo que quedaron bajo umbral.
        Artisan::call('inventory:check-alerts');
        $this->command?->info('✅ Alertas de stock generadas para productos en nivel crítico.');
    }
}
