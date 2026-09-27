<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function atributo(): BelongsTo
    {
        return $this->belongsTo(AtributoTecnico::class, 'atributo_id');
    }

    public function valorEnum(): BelongsTo
    {
        return $this->belongsTo(ValorAtributo::class, 'valor_enum_id');
    }

    /**
     * Valor crudo según el tipo de dato del atributo.
     */
    public function getValorAttribute(): mixed
    {
        return match ($this->atributo?->tipo_dato) {
            'string' => $this->valor_string,
            'integer' => $this->valor_integer,
            'decimal' => $this->valor_decimal,
            'boolean' => $this->valor_boolean,
            'enum' => $this->valorEnum?->valor,
            default => null,
        };
    }

    /**
     * Valor formateado para mostrar en la interfaz.
     */
    public function getValorLegibleAttribute(): string
    {
        if ($this->atributo?->tipo_dato === 'boolean') {
            return match ($this->valor_boolean) {
                true => 'Sí',
                false => 'No',
                default => '—',
            };
        }

        $valor = $this->valor;

        return ($valor === null || $valor === '') ? '—' : (string) $valor;
    }
}
