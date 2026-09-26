<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoAtributo extends Model
{
    protected $table = 'producto_atributos';

    protected $fillable = [
        'producto_id',
        'atributo_id',
        'valor_string',
        'valor_integer',
        'valor_decimal',
        'valor_boolean',
        'valor_enum_id',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function atributo()
    {
        return $this->belongsTo(AtributoTecnico::class, 'atributo_id');
    }

    public function valorEnum()
    {
        return $this->belongsTo(ValorAtributo::class, 'valor_enum_id');
    }

    // Helper para obtener el valor según el tipo de dato
    public function getValorAttribute()
    {
        $tipo = $this->atributo->tipo_dato ?? null;

        return match ($tipo) {
            'string' => $this->valor_string,
            'integer' => $this->valor_integer,
            'decimal' => $this->valor_decimal,
            'boolean' => $this->valor_boolean,
            'enum' => $this->valorEnum->valor ?? null,
            default => null,
        };
    }
}