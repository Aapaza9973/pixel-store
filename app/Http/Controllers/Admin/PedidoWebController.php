<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pedidos del catálogo público (origen web).
 *
 * Vive separado del POS para no mezclar el mostrador con el flujo online:
 * acá se cobra un pedido pendiente o se cancela devolviendo el stock.
 */
class PedidoWebController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('ver ventas'), 403);

        $filtros = $request->validate([
            'estado' => ['nullable', 'string', Rule::in(['Pendiente', 'Pagado', 'Cancelada'])],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $termino = trim((string) ($filtros['buscar'] ?? ''));

        $pedidos = Venta::query()
            ->with(['cliente', 'detalles'])
            ->where('origen', 'web')
            ->when(filled($filtros['estado'] ?? null), fn ($query) => $query->where('estado', $filtros['estado']))
            ->when($termino !== '', fn ($query) => $query->whereHas(
                'cliente',
                fn ($cliente) => $cliente->buscar($termino)
            ))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $pendientes = Venta::query()
            ->where('origen', 'web')
            ->where('estado', 'Pendiente')
            ->count();

        return view('admin.pedidos-web.index', compact('pedidos', 'filtros', 'pendientes'));
    }

    public function show(Request $request, Venta $venta): View
    {
        abort_unless($request->user()?->can('ver ventas'), 403);
        abort_unless($venta->origen === 'web', 404);

        $venta->load(['cliente', 'detalles.producto']);

        return view('admin.pedidos-web.show', compact('venta'));
    }

    public function pagar(Request $request, Venta $venta, VentaService $ventas): RedirectResponse
    {
        abort_unless($request->user()?->can('editar ventas'), 403);

        try {
            $cobrado = $ventas->pagarPedidoWeb($venta, $request->user()->id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getUserMessage());
        }

        return back()->with('success', 'Pedido '.$this->numero($cobrado).' marcado como pagado.');
    }

    public function cancelar(Request $request, Venta $venta, VentaService $ventas): RedirectResponse
    {
        abort_unless($request->user()?->can('cancelar ventas'), 403);

        try {
            $cancelado = $ventas->cancelarPedidoWeb($venta, $request->user()->id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getUserMessage());
        }

        $unidades = (int) $cancelado->detalles->sum('cantidad');

        return back()->with(
            'success',
            'Pedido '.$this->numero($cancelado).' cancelado. '
            ."Se reingresaron {$unidades} unidad(es) al stock."
        );
    }

    private function numero(Venta $venta): string
    {
        return '#'.str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT);
    }
}
