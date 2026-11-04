<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OrdenCompra;
use App\Models\User;

class OrdenCompraPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver ordenes-compra');
    }

    public function view(User $user, OrdenCompra $orden): bool
    {
        return $user->can('ver ordenes-compra');
    }

    public function create(User $user): bool
    {
        return $user->can('crear ordenes-compra');
    }

    public function update(User $user, OrdenCompra $orden): bool
    {
        return $user->can('editar ordenes-compra');
    }

    /**
     * RoleSeeder no define 'eliminar ordenes-compra': una OC no se borra, se
     * cancela cambiando el estado. La baja queda igual restringida a los
     * mismos roles que el resto del módulo.
     */
    public function delete(User $user, OrdenCompra $orden): bool
    {
        return $user->hasAnyRole(['Admin', 'Inventario']);
    }
}
