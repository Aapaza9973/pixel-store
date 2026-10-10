<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\InventarioService;
use Illuminate\View\View;

class InventarioController extends Controller
{
    /**
     * Panel de control del módulo de inventario.
     *
     * El acceso está restringido por el middleware `inventario.access`
     * (permiso `ver inventario`; Admin pasa por Gate::before).
     */
    public function index(InventarioService $inventario): View
    {
        return view('admin.inventario.index', $inventario->resumen());
    }
}
