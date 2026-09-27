<?php

namespace Database\Seeders;

use App\Models\AtributoTecnico;
use App\Models\Categoria;
use Illuminate\Database\Seeder;

class AtributoTecnicoSeeder extends Seeder
{
    /**
     * Atributos técnicos (EAV) asociados a cada categoría.
     * Un mismo nombre puede repetirse en categorías distintas
     * (socket, tipo_ram, chipset, frecuencia).
     */
    public function run(): void
    {
        $categorias = Categoria::query()->pluck('id', 'nombre');

        $atributos = [
            'Procesadores' => [
                ['nombre' => 'socket', 'tipo_dato' => 'enum', 'unidad' => null, 'es_filtrable' => true],
                ['nombre' => 'núcleos', 'tipo_dato' => 'integer', 'unidad' => null, 'es_filtrable' => true],
                ['nombre' => 'hilos', 'tipo_dato' => 'integer', 'unidad' => null, 'es_filtrable' => false],
                ['nombre' => 'frecuencia', 'tipo_dato' => 'decimal', 'unidad' => 'GHz', 'es_filtrable' => true],
                ['nombre' => 'tdp', 'tipo_dato' => 'integer', 'unidad' => 'W', 'es_filtrable' => false],
            ],
            'Memorias RAM' => [
                ['nombre' => 'tipo_ram', 'tipo_dato' => 'enum', 'unidad' => null, 'es_filtrable' => true],
                ['nombre' => 'capacidad', 'tipo_dato' => 'integer', 'unidad' => 'GB', 'es_filtrable' => true],
                ['nombre' => 'frecuencia', 'tipo_dato' => 'integer', 'unidad' => 'MHz', 'es_filtrable' => true],
            ],
            'Tarjetas madre' => [
                ['nombre' => 'socket', 'tipo_dato' => 'enum', 'unidad' => null, 'es_filtrable' => true],
                ['nombre' => 'chipset', 'tipo_dato' => 'string', 'unidad' => null, 'es_filtrable' => true],
                ['nombre' => 'tipo_ram', 'tipo_dato' => 'enum', 'unidad' => null, 'es_filtrable' => true],
            ],
            'Tarjetas gráficas' => [
                ['nombre' => 'chipset', 'tipo_dato' => 'string', 'unidad' => null, 'es_filtrable' => true],
                ['nombre' => 'vram', 'tipo_dato' => 'integer', 'unidad' => 'GB', 'es_filtrable' => true],
                ['nombre' => 'longitud', 'tipo_dato' => 'decimal', 'unidad' => 'mm', 'es_filtrable' => false],
            ],
            'Fuentes de poder' => [
                ['nombre' => 'vatios', 'tipo_dato' => 'integer', 'unidad' => 'W', 'es_filtrable' => true],
                ['nombre' => 'certificacion', 'tipo_dato' => 'enum', 'unidad' => null, 'es_filtrable' => true],
            ],
            'Laptops' => [
                ['nombre' => 'tamaño_pantalla', 'tipo_dato' => 'decimal', 'unidad' => 'pulgadas', 'es_filtrable' => true],
                ['nombre' => 'procesador', 'tipo_dato' => 'string', 'unidad' => null, 'es_filtrable' => false],
                ['nombre' => 'ram', 'tipo_dato' => 'integer', 'unidad' => 'GB', 'es_filtrable' => true],
                ['nombre' => 'almacenamiento', 'tipo_dato' => 'integer', 'unidad' => 'GB', 'es_filtrable' => true],
                ['nombre' => 'sistema_operativo', 'tipo_dato' => 'enum', 'unidad' => null, 'es_filtrable' => true],
            ],
        ];

        foreach ($atributos as $nombreCategoria => $items) {
            $categoriaId = $categorias[$nombreCategoria] ?? null;

            if (! $categoriaId) {
                continue;
            }

            foreach ($items as $orden => $atributo) {
                AtributoTecnico::firstOrCreate(
                    [
                        'nombre' => $atributo['nombre'],
                        'categoria_id' => $categoriaId,
                    ],
                    [
                        'tipo_dato' => $atributo['tipo_dato'],
                        'unidad' => $atributo['unidad'],
                        'es_filtrable' => $atributo['es_filtrable'],
                        'es_comparable' => $atributo['es_comparable'] ?? true,
                        'orden' => $orden,
                    ]
                );
            }
        }

        $this->command?->info('✅ '.AtributoTecnico::count().' atributos técnicos creados.');
    }
}
