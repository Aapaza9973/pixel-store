<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cotizacion;
use App\Models\User;

class CotizacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver cotizaciones');
    }

    public function view(User $user, Cotizacion $cotizacion): bool
    {
        return $user->can('ver cotizaciones');
    }

    public function create(User $user): bool
    {
        return $user->can('crear cotizaciones');
    }

    public function update(User $user, Cotizacion $cotizacion): bool
    {
        return $user->can('editar cotizaciones');
    }

    public function delete(User $user, Cotizacion $cotizacion): bool
    {
        return $user->can('eliminar cotizaciones');
    }

    public function anular(User $user, Cotizacion $cotizacion): bool
    {
        return $user->can('editar cotizaciones') && $cotizacion->estado === 'Vigente';
    }

    public function convertir(User $user, Cotizacion $cotizacion): bool
    {
        return $user->can('crear ventas') && $cotizacion->estado === 'Vigente';
    }
}
