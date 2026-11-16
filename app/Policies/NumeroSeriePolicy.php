<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NumeroSerie;
use App\Models\User;

/**
 * Autorización del módulo de números de serie.
 *
 * Los permisos ya existen en el RoleSeeder: Admin e Inventario pueden ver y
 * editar; el Vendedor solo consulta.
 */
class NumeroSeriePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver numeros-serie');
    }

    public function view(User $user, NumeroSerie $numeroSerie): bool
    {
        return $user->can('ver numeros-serie');
    }

    public function create(User $user): bool
    {
        return $user->can('editar numeros-serie');
    }

    public function update(User $user, NumeroSerie $numeroSerie): bool
    {
        return $user->can('editar numeros-serie');
    }

    public function delete(User $user, NumeroSerie $numeroSerie): bool
    {
        // Una serie vendida es parte de la trazabilidad: no se borra.
        return $user->can('editar numeros-serie') && $numeroSerie->estaDisponible();
    }
}
