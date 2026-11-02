<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Número de serie de un producto.
 *
 * Estados canónicos: disponible, vendido, reservado, devuelto, defectuoso, baja.
 *
 * @property int $id
 * @property int $producto_id
 * @property string $numero_serie
 * @property string $estado
 * @property int|null $venta_id
 * @property int|null $cliente_id
 * @property string|null $costo_unitario
 * @property \Carbon\Carbon $fecha_ingreso
 * @property \Carbon\Carbon|null $fecha_venta
 * @property \Carbon\Carbon|null $garantia_hasta
 * @property int|null $ubicacion_id
 */
class NumeroSerie extends Model
{
    use HasFactory;

    protected $table = 'numeros_serie';

    protected $fillable = [
        'producto_id', 'numero_serie', 'estado', 'venta_id', 'cliente_id',
        'costo_unitario', 'fecha_ingreso', 'fecha_venta', 'garantia_hasta',
        'ubicacion_id', 'notas',
    ];

    protected $casts = [
        'costo_unitario' => 'decimal:2',
        'fecha_ingreso' => 'datetime',
        'fecha_venta' => 'datetime',
        'garantia_hasta' => 'date',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function scopeDisponibles($query)
    {
        return $query->where('estado', 'disponible');
    }

    public function estaDisponible(): bool
    {
        return $this->estado === 'disponible';
    }
}
