<?php

namespace App\Http\Controllers;

use App\Models\AlertaStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AlertaController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(AlertaStock::class, 'alerta');
    }

    public function index(): View
    {
        $alertas = AlertaStock::query()
            ->with('producto.categoria')
            ->orderBy('leida')
            ->orderByDesc('created_at')
            ->paginate(15);

        $noLeidas = AlertaStock::query()->noLeidas()->count();

        return view('alertas.index', compact('alertas', 'noLeidas'));
    }

    public function marcarLeida(AlertaStock $alerta): RedirectResponse
    {
        $this->authorize('update', $alerta);

        $alerta->update(['leida' => true]);

        return back()->with('success', 'Alerta marcada como leída.');
    }

    public function marcarTodas(): RedirectResponse
    {
        $this->authorize('update', new AlertaStock);

        $actualizadas = AlertaStock::query()->noLeidas()->update(['leida' => true]);

        return back()->with('success', $actualizadas.' alertas marcadas como leídas.');
    }

    public function destroy(AlertaStock $alerta): RedirectResponse
    {
        $alerta->delete();

        return back()->with('success', 'Alerta eliminada.');
    }
}
