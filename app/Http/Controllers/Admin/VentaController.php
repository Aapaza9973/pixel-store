<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Punto de venta (POS).
 *
 * Día 1: solo el layout base con el carrito Alpine.
 * La búsqueda de productos y la confirmación llegan en el Día 2+ (VentaService).
 */
class VentaController extends Controller
{
    public function create(): View
    {
        return view('admin.ventas.create');
    }
}
