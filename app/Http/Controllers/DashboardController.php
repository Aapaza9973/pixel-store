<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(InventoryService $inventario): View
    {
        return view('dashboard', $inventario->resumen());
    }
}
