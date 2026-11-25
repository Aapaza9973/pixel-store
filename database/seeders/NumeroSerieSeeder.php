<?php

namespace Database\Seeders;

use App\Models\NumeroSerie;
use App\Models\Producto;
use App\Models\Ubicacion;
use Illuminate\Database\Seeder;

class NumeroSerieSeeder extends Seeder
{
    /**
     * Series disponibles para los productos demo que las manejan.
     *
     * Sin esto, cualquier venta de un producto serializado se bloquea en el POS
     * ("Este producto no tiene series disponibles") y la demo no se puede
     * completar. Se crea una serie por unidad en stock para que la invariante
     * stock = series disponibles se cumpla desde el primer arranque.
     */
    public function run(): void
    {
        if (Producto::query()->count() === 0) {
            $this->call(DemoProductoSeeder::class);
        }

        $tiendaId = Ubicacion::query()->where('tipo', 'tienda')->orderBy('id')->value('id');

        $creadas = 0;

        foreach (Producto::query()->where('maneja_numero_serie', true)->orderBy('id')->get() as $producto) {
            if (! $producto->stock || $producto->stock < 1) {
                continue;
            }

            $prefijo = $producto->sku ?: 'PROD-'.$producto->id;

            for ($unidad = 1; $unidad <= $producto->stock; $unidad++) {
                $numero = sprintf('%s-%04d', $prefijo, $unidad);

                $serie = NumeroSerie::firstOrCreate(
                    ['producto_id' => $producto->id, 'numero_serie' => $numero],
                    [
                        'estado' => 'disponible',
                        'costo_unitario' => $producto->costo,
                        'fecha_ingreso' => now()->subDays(30),
                        'ubicacion_id' => $tiendaId,
                    ]
                );

                if ($serie->wasRecentlyCreated) {
                    $creadas++;
                }
            }
        }

        $this->command?->info('✅ '.$creadas.' números de serie disponibles creados.');
    }
}
