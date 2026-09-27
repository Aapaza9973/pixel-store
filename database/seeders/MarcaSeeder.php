<?php

namespace Database\Seeders;

use App\Models\Marca;
use Illuminate\Database\Seeder;

class MarcaSeeder extends Seeder
{
    /**
     * Marcas reales del rubro, agrupadas por tipo de producto.
     */
    public function run(): void
    {
        $marcas = [
            // Componentes
            'Intel', 'AMD', 'NVIDIA', 'ASUS', 'MSI', 'Gigabyte', 'Corsair', 'EVGA',
            // Laptops / equipos
            'HP', 'Dell', 'Lenovo', 'Apple', 'Acer',
            // Periféricos y accesorios
            'Logitech', 'Razer',
            // Almacenamiento y memorias
            'Kingston', 'Western Digital', 'Seagate', 'Samsung',
        ];

        foreach ($marcas as $nombre) {
            Marca::firstOrCreate(['nombre' => $nombre]);
        }

        $this->command?->info('✅ '.Marca::count().' marcas creadas.');
    }
}
