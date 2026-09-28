<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductoRequest;
use App\Http\Requests\Admin\TransferirStockRequest;
use App\Http\Requests\Admin\UpdateProductoRequest;
use App\Models\AtributoTecnico;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoAtributo;
use App\Models\Ubicacion;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;


class MovimientoController extends Controller
{

public function registrarSalida(Request $request, InventoryService $inventoryService)
{
    $request->validate([
        'producto_id' => 'required|exists:productos,id',
        'cantidad' => 'required|integer|min:1',
        'ubicacion_id' => 'required|exists:ubicaciones,id',
    ]);

    try {
        // Se envía la cantidad en negativo para registrar salida
        $inventoryService->ajustarStock(
            producto: Producto::findOrFail($request->producto_id),
            cantidad: -$request->cantidad,
            tipo: 'salida',
            motivo: $request->motivo,
            ubicacionId: $request->ubicacion_id,
            userId: auth()->user
        );

        return redirect()->back()->with('success', 'Salida registrada correctamente.');
    } catch (StockInsuficienteException $e) {
        // Retorna el error de stock insuficiente al formulario sin romper la aplicación
        return redirect()->back()->withInput()->withErrors(['cantidad' => $e->getMessage()]);
    }
}
}