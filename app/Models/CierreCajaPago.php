<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea por método de pago de un cierre de caja.
 *
 * @property int $id
 * @property int $cierre_caja_id
 * @property string $metodo Efectivo|Tarjeta|Transferencia|QR|Otro
 * @property string $monto_sistema
 * @property string $monto_real
 */
class CierreCajaPago extends Model
{
    use HasFactory;

    protected $table = 'cierre_caja_pagos';

    protected $fillable = [
        'cierre_caja_id', 'metodo', 'monto_sistema', 'monto_real',
    ];

    protected $casts = [
        'monto_sistema' => 'decimal:2',
        'monto_real' => 'decimal:2',
    ];

    public function cierre(): BelongsTo
    {
        return $this->belongsTo(CierreCaja::class, 'cierre_caja_id');
    }
}
