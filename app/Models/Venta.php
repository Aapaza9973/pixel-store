<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Venta (cabecera). La confirmación atómica vive en VentaService.
 *
 * @property int $id
 * @property string|null $operacion_id
 * @property \Carbon\Carbon $fecha
 * @property int $user_id
 * @property int|null $cliente_id
 * @property string $total
 * @property string $descuento
 * @property string $descuento_porcentaje
 * @property int|null $autorizado_por
 * @property int $puntos_canjeados
 * @property string $estado
 * @property int|null $cotizacion_id
 */
class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'operacion_id', 'fecha', 'user_id', 'cliente_id', 'total',
        'descuento', 'descuento_porcentaje', 'autorizado_por',
        'puntos_canjeados', 'estado', 'cotizacion_id',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'total' => 'decimal:2',
        'descuento' => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'puntos_canjeados' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'venta_id');
    }

    public function devoluciones(): HasMany
    {
        return $this->hasMany(Devolucion::class, 'venta_id');
    }

    public function scopeDelDia($query)
    {
        return $query->whereDate('fecha', now()->toDateString());
    }

    public function estaPagada(): bool
    {
        return $this->estado === 'Pagado';
    }
}
