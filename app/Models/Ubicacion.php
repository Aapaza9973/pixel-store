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
}
