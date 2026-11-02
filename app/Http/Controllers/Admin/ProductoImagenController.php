<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Galería de imágenes de producto.
 *
 * Se mantiene separado de ProductoController para no mezclar el CRUD de
 * producto con la subida de archivos. Sin redimensionado todavía: llega en
 * el Día 2 (intervention/image ya está instalado).
 */
class ProductoImagenController extends Controller
{
    public function store(Request $request, Producto $producto): RedirectResponse
    {
        $this->authorize('update', $producto);

        $request->validate([
            'imagenes' => ['required', 'array', 'max:5'],
            'imagenes.*' => ['image', 'max:4096'],
        ], [
            'imagenes.required' => 'Seleccione al menos una imagen.',
            'imagenes.max' => 'Puede subir hasta 5 imágenes por vez.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen válida.',
            'imagenes.*.max' => 'Cada imagen no puede superar 4 MB.',
        ]);

        $orden = (int) $producto->imagenesGaleria()->max('orden');
        $hayPrincipal = $producto->imagenesGaleria()->where('es_principal', true)->exists();

        foreach ($request->file('imagenes') as $archivo) {
            $path = $archivo->store("productos/{$producto->id}", 'public');

            ProductoImagen::create([
                'producto_id' => $producto->id,
                'path' => $path,
                'alt' => $producto->nombre,
                'orden' => ++$orden,
                'es_principal' => ! $hayPrincipal,
            ]);

            $hayPrincipal = true;
        }

        return back()->with('success', 'Imágenes cargadas correctamente.');
    }

    public function destroy(Producto $producto, ProductoImagen $imagen): RedirectResponse
    {
        $this->authorize('update', $producto);

        abort_unless($imagen->producto_id === $producto->id, 404);

        Storage::disk('public')->delete($imagen->path);
        $imagen->delete();

        return back()->with('success', 'Imagen eliminada.');
    }
}
