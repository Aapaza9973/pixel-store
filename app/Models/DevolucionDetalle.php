<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de devolución (antes tabla `devoluciones`).
 *
 * @property int $id
 * @property int|null $devolucion_id
 * @property int $venta_id
 * @property int $producto_id
 * @property int|null $numero_serie_id
 * @property int $cantidad
 * @property string $motivo
 * @property string $estado
 * @property string|null $monto_reembolso
 */
class DevolucionDetalle extends Model
{
    use HasFactory;

    protected $table = 'devolucion_detalles';

    protected $fillable = [
        'devolucion_id', 'venta_id', 'producto_id', 'numero_serie_id',
        'cantidad', 'motivo', 'estado', 'monto_reembolso', 'user_id',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'monto_reembolso' => 'decimal:2',
    ];

    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(Devolucion::class, 'devolucion_id');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function numeroSerie(): BelongsTo
    {
        return $this->belongsTo(NumeroSerie::class, 'numero_serie_id');
    }
}
