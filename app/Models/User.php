<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuario del sistema (panel interno y tienda).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $telefono
 * @property string|null $nit_ci
 * @property string $pref_papel_comprobante
 * @property bool $pref_imprimir_pos
 * @property bool $activo
 * @property string|null $remember_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read string $rol_principal
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MovimientoStock> $movimientosStock
 *
 * @method static Builder|User activos()
 * @method static Builder|User inactivos()
 * @method static Builder|User buscar(string $termino)
 */
class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    /** Preferencia de impresión en papel térmico (POS). */
    public const PAPEL_TERMICO = 'termico';

    /** Preferencia de impresión en papel carta (formato completo). */
    public const PAPEL_CARTA = 'carta';

    /** Valores válidos para `pref_papel_comprobante` (enum_papel_comprobante). */
    public const PAPELES = [
        self::PAPEL_TERMICO,
        self::PAPEL_CARTA,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'nit_ci',
        'pref_papel_comprobante',
        'pref_imprimir_pos',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'pref_imprimir_pos' => 'boolean',
        ];
    }

    // ============ RELACIONES ============

    /**
     * Movimientos de stock registrados por este usuario.
     */
    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class);
    }

    // TODO(sprint-2): crear modelo Venta
    // public function ventas(): HasMany
    // {
    //     return $this->hasMany(Venta::class);
    // }

    // TODO(sprint-2): crear modelo CierreCaja
    // public function cierresCaja(): HasMany
    // {
    //     return $this->hasMany(CierreCaja::class);
    // }

    // ============ SCOPES ============

    /**
     * Solo usuarios con la cuenta habilitada.
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /**
     * Solo usuarios con la cuenta deshabilitada.
     */
    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('activo', false);
    }

    /**
     * Búsqueda por nombre o correo (case-insensitive).
     */
    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        return $query->where(function (Builder $q) use ($termino): void {
            $q->where('name', 'ILIKE', "%{$termino}%")
                ->orWhere('email', 'ILIKE', "%{$termino}%");
        });
    }

    // ============ MÉTODOS DE NEGOCIO ============

    /**
     * Indica si la cuenta está habilitada.
     */
    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }

    /**
     * Indica si el usuario tiene el rol Admin.
     */
    public function esAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    /**
     * Panel inicial al que se redirige al usuario tras el login.
     *
     * Admin y demás roles van al dashboard general; el Encargado de
     * inventario va directo a su módulo.
     */
    public function panelHome(): string
    {
        if ($this->hasRole('Inventario') && ! $this->hasRole('Admin')) {
            return route('admin.inventario.index', absolute: false);
        }

        return route('dashboard', absolute: false);
    }

    /**
     * Nombre del primer rol asignado (o cadena por defecto).
     */
    public function getRolPrincipalAttribute(): string
    {
        return $this->getRoleNames()->first() ?? 'Sin rol';
    }
}
