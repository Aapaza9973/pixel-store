<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Factura electrónica de una venta (HU-4.1).
 *
 * El esquema viene del SIN: `cuf` y `cufd` identifican el documento, y
 * `respuesta_sin` guarda la respuesta del proveedor cuando exista integración.
 *
 * @property int $id
 * @property int $venta_id
 * @property int|null $user_id
 * @property string $numero_factura
 * @property string $cuf
 * @property string $cufd
 * @property string $estado Pendiente|Emitida|Anulada|Error
 * @property \Carbon\Carbon $fecha_emision
 * @property \Carbon\Carbon|null $fecha_anulacion
 * @property string|null $motivo_anulacion
 * @property string|null $xml
 */
class FacturaElectronica extends Model
{
    use HasFactory;

    public const ESTADOS = ['Pendiente', 'Emitida', 'Anulada', 'Error'];

    protected $table = 'facturas_electronicas';

    protected $fillable = [
        'venta_id', 'user_id', 'numero_factura', 'cuf', 'cufd', 'estado',
        'fecha_emision', 'fecha_anulacion', 'motivo_anulacion', 'xml',
        'respuesta_sin',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_anulacion' => 'datetime',
        'respuesta_sin' => 'array',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estaAnulada(): bool
    {
        return $this->estado === 'Anulada';
    }

    public function scopeEmitidas($query)
    {
        return $query->where('estado', 'Emitida');
    }
}
