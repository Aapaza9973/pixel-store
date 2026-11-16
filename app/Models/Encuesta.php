<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Encuesta anónima de satisfacción (HU-3.5).
 *
 * @property int $id
 * @property int|null $venta_id
 * @property int $atencion 1-5
 * @property int $catalogo 1-5
 * @property int $recomendacion 1-5
 * @property string|null $comentario
 * @property \Carbon\Carbon $created_at
 */
class Encuesta extends Model
{
    use HasFactory;

    protected $table = 'encuestas';

    protected $fillable = [
        'venta_id', 'atencion', 'catalogo', 'recomendacion', 'comentario',
    ];

    protected $casts = [
        'atencion' => 'integer',
        'catalogo' => 'integer',
        'recomendacion' => 'integer',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    /**
     * Promedio de las tres preguntas, redondeado a un decimal.
     */
    public function promedio(): float
    {
        return round(($this->atencion + $this->catalogo + $this->recomendacion) / 3, 1);
    }
}
