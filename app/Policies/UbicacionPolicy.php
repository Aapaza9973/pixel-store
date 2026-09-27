<?php

namespace App\Policies;

use App\Models\Ubicacion;
use App\Models\User;

class UbicacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver ubicaciones');
    }

    public function view(User $user, Ubicacion $ubicacion): bool
    {
        return $user->can('ver ubicaciones');
    }

    public function create(User $user): bool
    {
        return $user->can('crear ubicaciones');
    }

    public function update(User $user, Ubicacion $ubicacion): bool
    {
        return $user->can('editar ubicaciones');
    }

    public function delete(User $user, Ubicacion $ubicacion): bool
    {
        return $user->can('eliminar ubicaciones');
    }
}
