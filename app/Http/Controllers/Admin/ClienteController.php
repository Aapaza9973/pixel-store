<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClienteRequest;
use App\Http\Requests\Admin\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Cliente::class, 'cliente');
    }

    public function index(Request $request): View
    {
        $clientes = Cliente::query()
            ->when($request->filled('buscar'), fn ($query) => $query->buscar((string) $request->input('buscar')))
            ->when($request->filled('estado'), fn ($query) => $query->where(
                'activo',
                $request->input('estado') === 'activos'
            ))
            ->withCount('ventas')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('admin.clientes.index', compact('clientes'));
    }

    public function create(): View
    {
        return view('admin.clientes.create');
    }

    public function store(StoreClienteRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            Cliente::create($request->validated());
        });

        return redirect()
            ->route('admin.clientes.index')
            ->with('success', 'Cliente creado exitosamente.');
    }

    public function show(Cliente $cliente): View
    {
        $cliente->loadCount('ventas');

        return view('admin.clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente): View
    {
        return view('admin.clientes.edit', compact('cliente'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        DB::transaction(function () use ($request, $cliente) {
            $cliente->update($request->validated());
        });

        return redirect()
            ->route('admin.clientes.index')
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->delete();

        return redirect()
            ->route('admin.clientes.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }

    /**
     * Búsqueda ligera para el selector de cliente del POS (HU-5.5).
     */
    public function buscar(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $termino = trim($request->string('q')->toString());

        $clientes = Cliente::query()
            ->activos()
            ->when($termino !== '', fn ($query) => $query->buscar($termino))
            ->orderBy('nombre')
            ->limit(10)
            ->get(['id', 'nombre', 'nit_ci', 'telefono', 'email']);

        return response()->json(['data' => $clientes]);
    }
}
