<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Catálogo base de categorías de Pixel Store.
     * El tipo respeta el enum enum_categoria_tipo del schema.
     */
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Procesadores', 'tipo' => 'Componente'],
            ['nombre' => 'Memorias RAM', 'tipo' => 'Componente'],
            ['nombre' => 'Tarjetas madre', 'tipo' => 'Componente'],
            ['nombre' => 'Tarjetas gráficas', 'tipo' => 'Componente'],
            ['nombre' => 'Fuentes de poder', 'tipo' => 'Componente'],
            ['nombre' => 'Gabinetes', 'tipo' => 'Componente'],
            ['nombre' => 'Almacenamiento', 'tipo' => 'Componente'],
            ['nombre' => 'Laptops', 'tipo' => 'Equipo'],
            ['nombre' => 'Monitores', 'tipo' => 'Periférico'],
            ['nombre' => 'Periféricos', 'tipo' => 'Periférico'],
            ['nombre' => 'Accesorios', 'tipo' => 'Accesorio'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::firstOrCreate(
                ['nombre' => $categoria['nombre']],
                ['tipo' => $categoria['tipo']]
            );
        }

        $this->command?->info('✅ '.Categoria::count().' categorías creadas.');
    }
}
