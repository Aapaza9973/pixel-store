<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de puntos de fidelidad (acumulado, canjeado o ajuste).
 *
 * @property int $id
 * @property int $cliente_id
 * @property int|null $venta_id
 * @property int|null $user_id
 * @property string $tipo acumulado|canjeado|ajuste
 * @property int $puntos
 * @property string $concepto
 */
class Punto extends Model
{
    use HasFactory;

    protected $table = 'puntos';

    protected $fillable = [
        'cliente_id', 'venta_id', 'user_id', 'tipo', 'puntos', 'concepto',
    ];

    protected $casts = [
        'puntos' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDelCliente($query, int $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }
}
