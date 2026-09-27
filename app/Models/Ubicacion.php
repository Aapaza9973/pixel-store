<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ubicacion extends Model
{
    protected $table = 'ubicaciones';

    protected $fillable = [
        'nombre',
        'tipo',
        'direccion',
        'pasillo',
        'estante',
        'anaquel',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function stockUbicaciones(): HasMany
    {
        return $this->hasMany(StockUbicacion::class);
    }

    public function estaActiva(): bool
    {
        return (bool) $this->activa;
    }

    /**
     * Nombre legible compuesto: nombre + pasillo + estante + anaquel.
     * Ej: "Tienda · Pasillo A · Estante 1".
     */
    public function getNombreCompletoAttribute(): string
    {
        return collect([$this->nombre, $this->pasillo, $this->estante, $this->anaquel])
            ->filter(fn ($valor) => $valor !== null && $valor !== '')
            ->implode(' · ');
    }

    /**
     * Ubicaciones que representan una subdivisión física concreta.
     */
    public function scopeSububicaciones($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('pasillo')
                ->orWhereNotNull('estante')
                ->orWhereNotNull('anaquel');
        });
    }
}
