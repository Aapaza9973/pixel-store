<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cierre de caja de un turno (HU-6.1).
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $fecha
 * @property string $turno mañana|tarde|noche
 * @property string $efectivo_esperado
 * @property string $efectivo_real
 * @property string $diferencia efectivo_real - efectivo_esperado
 * @property string|null $notas
 */
class CierreCaja extends Model
{
    use HasFactory;

    protected $table = 'cierres_caja';

    protected $fillable = [
        'user_id', 'fecha', 'turno', 'efectivo_esperado', 'efectivo_real', 'diferencia', 'notas',
    ];

    protected $casts = [
        'fecha' => 'date',
        'efectivo_esperado' => 'decimal:2',
        'efectivo_real' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(CierreCajaPago::class, 'cierre_caja_id');
    }
}
