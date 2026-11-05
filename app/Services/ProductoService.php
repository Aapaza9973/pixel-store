<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Collection;

/**
 * Consultas de catálogo (HU-2.5).
 */
class ProductoService
{
    /**
     * Productos de la misma categoría con precio a ±30 % del actual, ordenados
     * por cercanía de precio.
     *
     * `productos` no tiene columna `activo`: la visibilidad pública se controla
     * con `visible_catalogo` (schema canónico, igual que CatalogoController).
     *
     * @return Collection<int, Producto>
     */
    public function similares(Producto $producto, int $limite = 4): Collection
    {
        $precio = (float) $producto->precio_unitario;

        return Producto::query()
            ->with(['marca', 'imagenesGaleria'])
            ->where('categoria_id', $producto->categoria_id)
            ->whereKeyNot($producto->getKey())
            ->where('visible_catalogo', true)
            ->whereBetween('precio_unitario', [
                round($precio * 0.7, 2),
                round($precio * 1.3, 2),
            ])
            ->orderByRaw('abs(precio_unitario - ?) asc', [$precio])
            ->limit($limite)
            ->get();
    }

    /**
     * Productos de la misma categoría con precio FUERA del rango ±30 %: opciones
     * distintas en gama, a diferencia de `similares`.
     *
     * @return Collection<int, Producto>
     */
    public function alternativas(Producto $producto, int $limite = 4): Collection
    {
        $precio = (float) $producto->precio_unitario;

        return Producto::query()
            ->with(['marca', 'imagenesGaleria'])
            ->where('categoria_id', $producto->categoria_id)
            ->whereKeyNot($producto->getKey())
            ->where('visible_catalogo', true)
            ->where(function ($query) use ($precio) {
                $query->where('precio_unitario', '<', round($precio * 0.7, 2))
                    ->orWhere('precio_unitario', '>', round($precio * 1.3, 2));
            })
            ->orderByRaw('abs(precio_unitario - ?) asc', [$precio])
            ->limit($limite)
            ->get();
    }
}
