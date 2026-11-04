<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recepción parcial o total de una orden de compra.
 *
 * Solo registra el hecho y su autor; el ajuste de stock se implementa en
 * 3.4.7 (Día 3), no acá.
 *
 * @property int $id
 * @property int $orden_id
 * @property int $user_id
 * @property \Carbon\Carbon $fecha_recepcion
 * @property string|null $notas
 */
class OrdenCompraRecepcion extends Model
{
    use HasFactory;

    protected $table = 'orden_compra_recepcion';

    protected $fillable = [
        'orden_id', 'user_id', 'fecha_recepcion', 'notas',
    ];

    protected $casts = [
        'fecha_recepcion' => 'datetime',
    ];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
