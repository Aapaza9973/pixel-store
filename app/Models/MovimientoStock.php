<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de stock (entrada, salida, ajuste, venta, devolución, baja).
 *
 * @property int $id
 * @property int $producto_id
 * @property int|null $ubicacion_id
 * @property string $tipo
 * @property int $cantidad
 * @property int $stock_resultante
 * @property string|null $motivo
 * @property int|null $user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static Builder|MovimientoStock porTipo(string $tipo)
 * @method static Builder|MovimientoStock enRango(?string $desde, ?string $hasta)
 */
class MovimientoStock extends Model
{
    use HasFactory;

    protected $table = 'movimientos_stock';

    protected $fillable = [
        'producto_id',
        'ubicacion_id',
        'tipo',
        'cantidad',
        'stock_resultante',
        'motivo',
        'user_id',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'stock_resultante' => 'integer',
    ];

    // ============ RELACIONES ============

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ============ SCOPES ============

    /**
     * Filtra movimientos por tipo (entrada, salida, ajuste, venta, etc.).
     */
    public function scopePorTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Filtra movimientos por rango de fechas (inclusivo).
     */
    public function scopeEnRango(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        return $query
            ->when($desde, fn (Builder $q) => $q->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn (Builder $q) => $q->whereDate('created_at', '<=', $hasta));
    }

    // ============ MÉTODOS DE NEGOCIO ============

    /**
     * Indica si el movimiento representa una entrada de stock.
     */
    public function esEntrada(): bool
    {
        return $this->cantidad > 0;
    }

    /**
     * Indica si el movimiento representa una salida de stock.
     */
    public function esSalida(): bool
    {
        return $this->cantidad < 0;
    }
}
