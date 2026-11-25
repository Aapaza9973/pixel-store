<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id', 'marca_id', 'nombre', 'slug', 'descripcion',
        'precio_unitario', 'costo', 'stock', 'umbral_alerta',
        'maneja_numero_serie', 'sku', 'codigo_barras',
        'visible_catalogo', 'ocultar_si_sin_stock', 'imagen_principal', 'imagenes',
    ];

    /**
     * Todo producto necesita slug: es la URL pública de su ficha.
     * Se genera al guardar si no vino en el formulario.
     */
    protected static function booted(): void
    {
        static::saving(function (Producto $producto): void {
            if (blank($producto->slug)) {
                $producto->slug = static::slugUnico((string) $producto->nombre);
            }
        });
    }

    private static function slugUnico(string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'producto';
        $slug = $base;
        $sufijo = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$sufijo++;
        }

        return $slug;
    }

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'costo' => 'decimal:2',
        'maneja_numero_serie' => 'boolean',
        'visible_catalogo' => 'boolean',
        'ocultar_si_sin_stock' => 'boolean',
        'imagenes' => 'array',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function atributos(): HasMany
    {
        return $this->hasMany(ProductoAtributo::class);
    }

    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class);
    }

    public function alertasStock(): HasMany
    {
        return $this->hasMany(AlertaStock::class);
    }

    public function numerosSerie(): HasMany
    {
        return $this->hasMany(NumeroSerie::class, 'producto_id');
    }

    public function stockUbicaciones(): HasMany
    {
        return $this->hasMany(StockUbicacion::class);
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }

    /**
     * Galería normalizada. Se llama distinto de `imagenes` porque ese es el
     * nombre de la columna JSONB heredada del schema original.
     */
    public function imagenesGaleria(): HasMany
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id')->orderBy('orden');
    }

    /**
     * 2.8.4: visible en catálogo y, si el producto lo pide, con stock > 0.
     */
    public function scopeVisible($query)
    {
        return $query->where('visible_catalogo', true)
            ->where(function ($query) {
                $query->where('ocultar_si_sin_stock', false)
                    ->orWhere('stock', '>', 0);
            });
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock', '<=', 'umbral_alerta');
    }

    /**
     * Búsqueda por nombre, SKU o código de barras.
     *
     * Combina coincidencia exacta (ILIKE) con búsqueda difusa de Postgres:
     * `unaccent` ignora tildes y `similarity` (pg_trgm) tolera errores de
     * tipeo. Las extensiones las declara `database/schema/pgsql-schema.sql`.
     */
    public function scopeBuscar($query, string $termino)
    {
        $termino = trim($termino);

        if ($termino === '') {
            return $query;
        }

        $escapado = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $termino);
        $like = '%'.$escapado.'%';

        return $query->where(function ($q) use ($like, $termino) {
            $q->where('nombre', 'ILIKE', $like)
                ->orWhere('sku', 'ILIKE', $like)
                ->orWhere('codigo_barras', 'ILIKE', $like)
                ->orWhereRaw('unaccent(nombre) ILIKE unaccent(?)', [$like])
                // La difusa se aplica solo al nombre: el SKU es un identificador
                // exacto. Umbral 0.35 medido: un typo real da 0.5, mientras que
                // 'AMD Ryzen 9 5900X' contra 'CPU-AMD-5600X' da 0.28.
                ->orWhereRaw('similarity(lower(unaccent(nombre)), lower(unaccent(?))) >= 0.35', [$termino]);
        });
    }

    public function tieneStockBajo(): bool
    {
        return $this->stock <= $this->umbral_alerta;
    }
}
