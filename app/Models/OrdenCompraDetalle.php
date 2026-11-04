<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de orden de compra (tabla canónica `orden_compra_items`).
 *
 * @property int $id
 * @property int $orden_compra_id
 * @property int $producto_id
 * @property int $cantidad
 * @property string $precio_unitario
 * @property string $subtotal
 */
class OrdenCompraDetalle extends Model
{
    use HasFactory;

    protected $table = 'orden_compra_items';

    protected $fillable = [
        'orden_compra_id', 'producto_id', 'cantidad', 'precio_unitario', 'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Alias de `ordenCompra()` con el nombre que pide el contrato del Día 2.
     */
    public function orden(): BelongsTo
    {
        return $this->ordenCompra();
    }

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_compra_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
