<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago de una venta (tabla canónica `pagos`).
 *
 * @property int $id
 * @property int $venta_id
 * @property string $monto
 * @property string $metodo Efectivo|Tarjeta|Transferencia|QR|Otro
 * @property string $estado Completado|Pendiente|Cancelado
 * @property string|null $referencia
 */
class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'venta_id', 'monto', 'metodo', 'estado', 'referencia',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}
