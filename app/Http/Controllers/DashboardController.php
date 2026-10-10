<?php

namespace App\Http\Controllers;

use App\Services\InventarioService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(InventarioService $inventario): View
    {
        return view('dashboard', $inventario->resumen());
    }
}
