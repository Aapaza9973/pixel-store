<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
    }
}
