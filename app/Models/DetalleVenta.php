<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de venta (tabla canónica `detalle_ventas`).
 *
 * @property int $id
 * @property int $venta_id
 * @property int $producto_id
 * @property int|null $numero_serie_id
 * @property int $cantidad
 * @property string $precio_unitario
 * @property string $descuento_item
 */
class DetalleVenta extends Model
{
    use HasFactory;

    protected $table = 'detalle_ventas';

    protected $fillable = [
        'venta_id', 'producto_id', 'numero_serie_id',
        'cantidad', 'precio_unitario', 'descuento_item',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'descuento_item' => 'decimal:2',
    ];

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

    public function subtotal(): float
    {
        return (float) $this->precio_unitario * $this->cantidad - (float) $this->descuento_item;
    }
}
