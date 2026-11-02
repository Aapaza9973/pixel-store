<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cabecera de devolución (idempotente por `operacion_id`).
 *
 * @property int $id
 * @property string $operacion_id
 * @property int $venta_id
 * @property int $user_id
 * @property string $motivo
 * @property string $estado pendiente|aprobada|rechazada
 * @property string $total_devuelto
 */
class Devolucion extends Model
{
    use HasFactory;

    protected $table = 'devoluciones';

    protected $fillable = [
        'operacion_id', 'venta_id', 'user_id', 'motivo', 'estado', 'total_devuelto',
    ];

    protected $casts = [
        'total_devuelto' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DevolucionDetalle::class, 'devolucion_id');
    }

    public function estaAprobada(): bool
    {
        return $this->estado === 'aprobada';
    }
}
