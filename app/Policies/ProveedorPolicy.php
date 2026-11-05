<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Proveedor;
use App\Models\User;

class ProveedorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver proveedores');
    }

    public function view(User $user, Proveedor $proveedor): bool
    {
        return $user->can('ver proveedores');
    }

    public function create(User $user): bool
    {
        return $user->can('crear proveedores');
    }

    public function update(User $user, Proveedor $proveedor): bool
    {
        return $user->can('editar proveedores');
    }

    public function delete(User $user, Proveedor $proveedor): bool
    {
        return $user->can('eliminar proveedores');
    }
}
