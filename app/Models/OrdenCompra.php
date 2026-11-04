<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Orden de compra a proveedor.
 *
 * @property int $id
 * @property string $numero
 * @property \Carbon\Carbon|null $fecha_emision
 * @property \Carbon\Carbon|null $fecha_entrega
 * @property int $proveedor_id
 * @property int $user_id
 * @property \Carbon\Carbon $fecha
 * @property string $estado borrador|enviada|recibida_parcial|recibida_total|cancelada
 * @property string $subtotal
 * @property string $impuestos
 * @property string $total
 */
class OrdenCompra extends Model
{
    use HasFactory;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'numero', 'fecha_emision', 'fecha_entrega', 'proveedor_id', 'user_id',
        'fecha', 'estado', 'subtotal', 'impuestos', 'total', 'pdf_path', 'notas',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_entrega' => 'date',
        'fecha' => 'datetime',
        'subtotal' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(OrdenCompraDetalle::class, 'orden_compra_id');
    }

    public function recepciones(): HasMany
    {
        return $this->hasMany(OrdenCompraRecepcion::class, 'orden_id');
    }

    public function scopePorEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Búsqueda por número de orden o nombre del proveedor.
     */
    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('numero', 'ILIKE', "%{$termino}%")
                ->orWhereHas('proveedor', function ($proveedor) use ($termino) {
                    $proveedor->where('nombre', 'ILIKE', "%{$termino}%");
                });
        });
    }
}
