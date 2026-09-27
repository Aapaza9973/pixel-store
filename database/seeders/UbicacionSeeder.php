<?php

namespace Database\Seeders;

use App\Models\Ubicacion;
use Illuminate\Database\Seeder;

class UbicacionSeeder extends Seeder
{
    /**
     * Ubicaciones físicas donde se almacena el stock.
     */
    public function run(): void
    {
        $ubicaciones = [
            ['nombre' => 'Tienda (Exhibición)', 'tipo' => 'tienda', 'direccion' => 'Mostrador de ventas'],
            ['nombre' => 'Depósito Externo', 'tipo' => 'deposito', 'direccion' => 'Almacén principal'],
        ];

        foreach ($ubicaciones as $ubicacion) {
            Ubicacion::firstOrCreate(
                ['nombre' => $ubicacion['nombre']],
                [
                    'tipo' => $ubicacion['tipo'],
                    'direccion' => $ubicacion['direccion'],
                    'activa' => true,
                ]
            );
        }

        $this->command?->info('✅ '.Ubicacion::count().' ubicaciones creadas.');
    }
}
