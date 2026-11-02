<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver clientes');
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $user->can('ver clientes');
    }

    public function create(User $user): bool
    {
        return $user->can('crear clientes');
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->can('editar clientes');
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->can('eliminar clientes');
    }
}
