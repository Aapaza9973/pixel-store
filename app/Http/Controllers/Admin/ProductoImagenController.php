<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

/**
 * Galería de imágenes de producto.
 *
 * Se mantiene separado de ProductoController para no mezclar el CRUD de
 * producto con la subida de archivos. Cada archivo se normaliza a 1200 px de
 * lado máximo y se guarda un thumbnail de 400 px en `thumbs/`.
 */
class ProductoImagenController extends Controller
{
    private const MAX_LADO = 1200;

    private const LADO_THUMBNAIL = 400;

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

            $this->optimizar($path);

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

        $disco = Storage::disk('public');
        $disco->delete([$imagen->path, $imagen->pathThumbnail()]);
        $imagen->delete();

        return back()->with('success', 'Imagen eliminada.');
    }

    /**
     * Redimensiona el original y genera el thumbnail.
     *
     * Intervention v4 cambió la API: ya no existe `read()`, se usa
     * `decodePath()` y `scaleDown()` mantiene la proporción.
     */
    private function optimizar(string $path): void
    {
        $disco = Storage::disk('public');
        $absoluto = $disco->path($path);
        $manager = ImageManager::usingDriver(GdDriver::class);

        // `quality` solo lo aceptan los encoders con pérdida.
        $opciones = in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'webp'], true)
            ? ['quality' => 85]
            : [];

        $manager->decodePath($absoluto)
            ->scaleDown(width: self::MAX_LADO, height: self::MAX_LADO)
            ->save($absoluto, ...$opciones);

        $thumbnail = dirname($path).'/thumbs/'.basename($path);
        $disco->makeDirectory(dirname($thumbnail));

        $manager->decodePath($absoluto)
            ->scaleDown(width: self::LADO_THUMBNAIL, height: self::LADO_THUMBNAIL)
            ->save($disco->path($thumbnail), ...$opciones);
    }
}
