<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cotización formal para un cliente (HU Fase 2.1).
 *
 * @property int $id
 * @property string $numero
 * @property int|null $cliente_id
 * @property int $user_id
 * @property string $nombre_cliente
 * @property string|null $nit_ci
 * @property string|null $telefono
 * @property string|null $email
 * @property float $subtotal
 * @property float $descuento
 * @property float $total
 * @property int $validez_dias
 * @property \Carbon\Carbon $fecha_emision
 * @property \Carbon\Carbon $fecha_vencimiento
 * @property string $estado
 * @property string|null $pdf_path
 * @property string|null $notas
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static Builder|Cotizacion vigentes()
 * @method static Builder|Cotizacion buscar(string $termino)
 */
class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    /** Estados posibles de una cotización. */
    public const ESTADOS = ['Vigente', 'Vencida', 'Convertida', 'Anulada'];

    protected $fillable = [
        'numero', 'cliente_id', 'user_id', 'nombre_cliente', 'nit_ci',
        'telefono', 'email', 'subtotal', 'descuento', 'total',
        'validez_dias', 'fecha_emision', 'fecha_vencimiento', 'estado',
        'pdf_path', 'notas',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'validez_dias' => 'integer',
        'fecha_emision' => 'datetime',
        'fecha_vencimiento' => 'datetime',
    ];

    // ============ RELACIONES ============

    public function items(): HasMany
    {
        return $this->hasMany(CotizacionItem::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ============ SCOPES ============

    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('estado', 'Vigente');
    }

    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        return $query->where(function (Builder $q) use ($termino): void {
            $q->where('numero', 'ILIKE', "%{$termino}%")
                ->orWhere('nombre_cliente', 'ILIKE', "%{$termino}%");
        });
    }

    // ============ MÉTODOS DE NEGOCIO ============

    public function estaVigente(): bool
    {
        return $this->estado === 'Vigente';
    }

    public function estaVencida(): bool
    {
        return $this->estado === 'Vencida' || ($this->estado === 'Vigente' && $this->fecha_vencimiento->isPast());
    }
}
