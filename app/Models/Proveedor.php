<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Proveedor de mercadería.
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $nit
 * @property string|null $contacto
 * @property string|null $telefono
 * @property string|null $email
 * @property string|null $direccion
 * @property string $tipo Mayorista|Importador|Distribuidor|Local
 * @property string|null $condiciones
 * @property string|null $plazos_pago
 * @property string|null $tiempo_entrega
 * @property string $descuento_habitual
 * @property bool $activo
 */
class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre', 'nit', 'contacto', 'telefono', 'email', 'direccion',
        'tipo', 'condiciones', 'plazos_pago', 'tiempo_entrega',
        'descuento_habitual', 'activo',
    ];

    protected $casts = [
        'descuento_habitual' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(OrdenCompra::class, 'proveedor_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'ILIKE', "%{$termino}%")
                ->orWhere('nit', 'ILIKE', "%{$termino}%")
                ->orWhere('email', 'ILIKE', "%{$termino}%")
                ->orWhere('contacto', 'ILIKE', "%{$termino}%");
        });
    }

    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }

    public function tieneDependencias(): bool
    {
        return $this->ordenesCompra()->exists();
    }
}
