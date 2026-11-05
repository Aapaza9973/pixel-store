<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Imagen de la galería de un producto.
 *
 * @property int $id
 * @property int $producto_id
 * @property string $path
 * @property string|null $alt
 * @property int $orden
 * @property bool $es_principal
 */
class ProductoImagen extends Model
{
    use HasFactory;

    protected $table = 'producto_imagenes';

    protected $fillable = [
        'producto_id', 'path', 'alt', 'orden', 'es_principal',
    ];

    protected $casts = [
        'orden' => 'integer',
        'es_principal' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }

    /**
     * Ruta del thumbnail generado al subir (misma carpeta, subcarpeta thumbs/).
     */
    public function pathThumbnail(): string
    {
        return dirname($this->path).'/thumbs/'.basename($this->path);
    }

    /**
     * El thumbnail solo existe para las imágenes subidas desde el Día 2.
     * Las imágenes legacy (o las creadas por seeder) no tienen archivo en
     * `thumbs/`, así que se cae al original en lugar de mostrar una URL rota.
     */
    public function urlThumbnail(): string
    {
        if (Storage::disk('public')->exists($this->pathThumbnail())) {
            return asset('storage/'.$this->pathThumbnail());
        }

        return $this->url();
    }
}
