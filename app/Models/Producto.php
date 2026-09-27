<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id', 'marca_id', 'nombre', 'descripcion',
        'precio_unitario', 'costo', 'stock', 'umbral_alerta',
        'maneja_numero_serie', 'sku', 'codigo_barras',
        'visible_catalogo', 'imagen_principal', 'imagenes',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        'costo' => 'decimal:2',
        'maneja_numero_serie' => 'boolean',
        'visible_catalogo' => 'boolean',
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

    // public function movimientosStock(): HasMany { return $this->hasMany(MovimientoStock::class); }
    // public function alertasStock(): HasMany { return $this->hasMany(AlertaStock::class); }
    // public function numerosSerie(): HasMany { return $this->hasMany(NumeroSerie::class); }
    public function stockUbicaciones(): HasMany
    {
        return $this->hasMany(StockUbicacion::class);
    }
    // public function detalleVentas(): HasMany { return $this->hasMany(DetalleVenta::class); }

    public function scopeVisible($query)
    {
        return $query->where('visible_catalogo', true);
    }

    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock', '<=', 'umbral_alerta');
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'ILIKE', "%{$termino}%")
                ->orWhere('sku', 'ILIKE', "%{$termino}%")
                ->orWhere('codigo_barras', 'ILIKE', "%{$termino}%");
        });
    }

    public function tieneStockBajo(): bool
    {
        return $this->stock <= $this->umbral_alerta;
    }
}
