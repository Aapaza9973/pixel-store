<?php

namespace App\Policies;

use App\Models\AlertaStock;
use App\Models\User;

class AlertaStockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver productos');
    }

    public function update(User $user, AlertaStock $alerta): bool
    {
        return $user->can('editar productos');
    }

    public function delete(User $user, AlertaStock $alerta): bool
    {
        return $user->can('editar productos');
    }
}
