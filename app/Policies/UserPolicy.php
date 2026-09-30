<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determina si el usuario puede ver el listado de usuarios.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ver usuarios');
    }

    /**
     * Determina si el usuario puede ver el detalle de otro usuario.
     */
    public function view(User $user, User $target): bool
    {
        return $user->can('ver usuarios');
    }

    /**
     * Determina si el usuario puede crear nuevos usuarios.
     */
    public function create(User $user): bool
    {
        return $user->can('crear usuarios');
    }

    /**
     * Determina si el usuario puede editar a otro usuario.
     */
    public function update(User $user, User $target): bool
    {
        return $user->can('editar usuarios');
    }

    /**
     * Determina si el usuario puede eliminar a otro usuario.
     * Reglas:
     * - Requiere permiso 'eliminar usuarios'
     * - No permite auto-eliminación
     * - No permite eliminar al último Administrador
     */
    public function delete(User $user, User $target): bool
    {
        if (! $user->can('eliminar usuarios')) {
            return false;
        }

        if ($user->id === $target->id) {
            return false;
        }

        if ($target->hasRole('Admin') && User::role('Admin')->count() <= 1) {
            return false;
        }

        return true;
    }

    /**
     * Determina si el usuario puede desactivar a otro usuario.
     * Reglas:
     * - Requiere permiso 'editar usuarios'
     * - No permite auto-desactivación
     * - No permite desactivar al último Administrador
     */
    public function desactivar(User $user, User $target): bool
    {
        if (! $user->can('editar usuarios')) {
            return false;
        }

        if ($user->id === $target->id) {
            return false;
        }

        if ($target->hasRole('Admin') && User::role('Admin')->count() <= 1) {
            return false;
        }

        return true;
    }
}
