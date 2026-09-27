<?php

namespace Database\Seeders;

use App\Models\AtributoTecnico;
use App\Models\ValorAtributo;
use Illuminate\Database\Seeder;

class ValorAtributoSeeder extends Seeder
{
    /**
     * Valores permitidos para cada atributo de tipo enum.
     * Se aplican a todas las categorías que compartan el mismo nombre.
     */
    public function run(): void
    {
        $valores = [
            'socket' => ['AM4', 'AM5', 'LGA1700', 'LGA1200', 'LGA1851'],
            'tipo_ram' => ['DDR4', 'DDR5'],
            'sistema_operativo' => ['Windows 11', 'Windows 10', 'macOS', 'Linux'],
            'certificacion' => ['80+ Bronze', '80+ Gold', '80+ Platinum'],
        ];

        foreach ($valores as $nombreAtributo => $lista) {
            $atributos = AtributoTecnico::query()->where('nombre', $nombreAtributo)->get();

            foreach ($atributos as $atributo) {
                foreach ($lista as $orden => $valor) {
                    ValorAtributo::firstOrCreate(
                        [
                            'atributo_id' => $atributo->id,
                            'valor' => $valor,
                        ],
                        ['orden' => $orden]
                    );
                }
            }
        }

        $this->command?->info('✅ '.ValorAtributo::count().' valores de atributo creados.');
    }
}
