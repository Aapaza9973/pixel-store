<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Autorización de un descuento fuera del límite del rol (HU-5.11).
 *
 * Se crea ANTES de la venta, con el `operacion_id` como clave de idempotencia.
 * Al confirmar la venta (5.11.5) se completa `venta_id`; mientras tanto queda
 * como pendiente y el índice parcial impide dos pendientes por operación.
 *
 * @property int $id
 * @property string $operacion_id
 * @property int|null $venta_id
 * @property int $admin_id
 * @property int $vendedor_id
 * @property string $monto_descuento
 * @property string $descuento_porcentaje
 * @property string|null $motivo
 * @property string|null $ip
 */
class AutorizacionDescuento extends Model
{
    use HasFactory;

    protected $table = 'autorizaciones_descuento';

    protected $fillable = [
        'operacion_id', 'venta_id', 'admin_id', 'vendedor_id',
        'monto_descuento', 'descuento_porcentaje', 'motivo', 'ip',
    ];

    protected $casts = [
        'monto_descuento' => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    /**
     * Una autorización sigue pendiente mientras no se enlazó a su venta.
     */
    public function estaPendiente(): bool
    {
        return $this->venta_id === null;
    }
}
