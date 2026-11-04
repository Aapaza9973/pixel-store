<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de una recepción de orden de compra: cuántas unidades de una línea
 * de la orden llegaron en esa recepción.
 *
 * @property int $id
 * @property int $recepcion_id
 * @property int $orden_compra_item_id
 * @property int $cantidad
 */
class OrdenCompraRecepcionItem extends Model
{
    use HasFactory;

    protected $table = 'orden_compra_recepcion_items';

    protected $fillable = [
        'recepcion_id', 'orden_compra_item_id', 'cantidad',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(OrdenCompraRecepcion::class, 'recepcion_id');
    }

    public function ordenCompraItem(): BelongsTo
    {
        return $this->belongsTo(OrdenCompraDetalle::class, 'orden_compra_item_id');
    }
}
