<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'marca_id',
        'nombre',
        'descripcion',
        'precio_unitario',
        'costos',
        'stock',
        'umbral_alerta',
        'maneja_numero_serie',
        'sku',
        'codigo_barras',
        'visible_catalogo',
        'imagen_principal',
        'imagenes',
    ];

    protected $casts = [
        'maneja_numero_serie' => 'boolean',
        'visible_catalogo' => 'boolean',
        'imagenes' => 'array',
        'precio_unitario' => 'decimal:2',
        'costos' => 'decimal:2',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    public function atributosEav()
    {
        return $this->hasMany(ProductoAtributo::class, 'producto_id');
    }
}