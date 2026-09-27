<?php

namespace App\Policies;

use App\Models\Producto;
use App\Models\User;

class ProductoPolicy
{
    public function viewAny(User $user): bool { return $user->can('ver productos'); }
    public function view(User $user, Producto $producto): bool { return $user->can('ver productos'); }
    public function create(User $user): bool { return $user->can('crear productos'); }
    public function update(User $user, Producto $producto): bool { return $user->can('editar productos'); }
    public function delete(User $user, Producto $producto): bool { return $user->can('eliminar productos'); }
}
