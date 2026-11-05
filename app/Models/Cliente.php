<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cliente final de la tienda.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $nombre
 * @property string|null $email
 * @property string|null $telefono
 * @property string|null $nit_ci
 * @property string|null $direccion
 * @property bool $es_frecuente
 * @property bool $activo
 */
class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'user_id', 'nombre', 'email', 'telefono', 'nit_ci',
        'direccion', 'es_frecuente', 'activo',
    ];

    protected $casts = [
        'es_frecuente' => 'boolean',
        'activo' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'ILIKE', "%{$termino}%")
                ->orWhere('email', 'ILIKE', "%{$termino}%")
                ->orWhere('nit_ci', 'ILIKE', "%{$termino}%")
                ->orWhere('telefono', 'ILIKE', "%{$termino}%");
        });
    }

    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }
}
