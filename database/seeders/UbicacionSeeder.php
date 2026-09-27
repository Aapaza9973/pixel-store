<?php

namespace Database\Seeders;

use App\Models\Ubicacion;
use Illuminate\Database\Seeder;

class UbicacionSeeder extends Seeder
{
    /**
     * Ubicaciones físicas del almacén con su estructura interna.
     * El nombre_completo se compone como "nombre · pasillo · estante · anaquel".
     */
    public function run(): void
    {
        $ubicaciones = [
            // Tienda
            [
                'nombre' => 'Tienda', 'tipo' => 'tienda', 'direccion' => 'Mostrador de ventas',
                'pasillo' => 'Pasillo A', 'estante' => 'Estante 1', 'anaquel' => null,
            ],
            [
                'nombre' => 'Tienda', 'tipo' => 'tienda', 'direccion' => 'Mostrador de ventas',
                'pasillo' => 'Pasillo A', 'estante' => 'Estante 2', 'anaquel' => null,
            ],
            [
                'nombre' => 'Tienda · Mostrador', 'tipo' => 'tienda', 'direccion' => 'Mostrador de ventas',
                'pasillo' => null, 'estante' => null, 'anaquel' => null,
            ],

            // Depósito
            [
                'nombre' => 'Depósito', 'tipo' => 'deposito', 'direccion' => 'Almacén principal',
                'pasillo' => 'Pasillo 1', 'estante' => 'Estante A', 'anaquel' => 'Anaquel 1',
            ],
            [
                'nombre' => 'Depósito', 'tipo' => 'deposito', 'direccion' => 'Almacén principal',
                'pasillo' => 'Pasillo 1', 'estante' => 'Estante A', 'anaquel' => 'Anaquel 2',
            ],
            [
                'nombre' => 'Depósito', 'tipo' => 'deposito', 'direccion' => 'Almacén principal',
                'pasillo' => 'Pasillo 1', 'estante' => 'Estante B', 'anaquel' => null,
            ],
            [
                'nombre' => 'Depósito', 'tipo' => 'deposito', 'direccion' => 'Almacén principal',
                'pasillo' => 'Pasillo 2', 'estante' => 'Estante A', 'anaquel' => null,
            ],
        ];

        foreach ($ubicaciones as $ubicacion) {
            Ubicacion::updateOrCreate(
                [
                    'nombre' => $ubicacion['nombre'],
                    'pasillo' => $ubicacion['pasillo'],
                    'estante' => $ubicacion['estante'],
                    'anaquel' => $ubicacion['anaquel'],
                ],
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
