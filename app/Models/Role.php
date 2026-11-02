<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Rol del sistema con límite de descuento autorizado.
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property string $descuento_maximo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'descuento_maximo'];

    protected $casts = [
        'descuento_maximo' => 'decimal:2',
    ];
}
