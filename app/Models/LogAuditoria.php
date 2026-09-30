<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de auditoría del sistema.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $accion
 * @property string|null $modelo
 * @property int|null $modelo_id
 * @property array<string, mixed>|null $datos_anteriores
 * @property array<string, mixed>|null $datos_nuevos
 * @property string|null $ip
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 * @property-read \App\Models\User|null $user
 */
class LogAuditoria extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en base de datos.
     *
     * @var string
     */
    protected $table = 'logs_auditoria';

    /**
     * La tabla solo maneja created_at, sin updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'accion',
        'modelo',
        'modelo_id',
        'datos_anteriores',
        'datos_nuevos',
        'ip',
        'user_agent',
    ];

    /**
     * Conversión de tipos de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
        ];
    }

    // ============ RELACIONES ============

    /**
     * Usuario asociado al evento auditado.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
