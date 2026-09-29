<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MovimientoController extends Controller
{
    /**
     * Registrar una salida de stock desde el panel.
     *
     * Se usa cuando el admin necesita dar de baja stock sin que exista
     * una venta asociada (mermas, uso interno, pruebas, etc.).
     */
    public function registrarSalida(
        Request $request,
        InventoryService $inventoryService
    ): RedirectResponse {
        $validated = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'ubicacion_id' => ['required', 'exists:ubicaciones,id'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $inventoryService->ajustarStock(
                producto: Producto::findOrFail($validated['producto_id']),
                cantidad: -$validated['cantidad'],
                tipo: 'salida',
                motivo: $validated['motivo'] ?? null,
                ubicacionId: $validated['ubicacion_id'],
                userId: auth()->id(),
            );

            return redirect()
                ->back()
                ->with('success', 'Salida registrada correctamente.');

        } catch (StockInsuficienteException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['cantidad' => $e->getMessage()]);
        }
    }
}
