<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProveedorRequest;
use App\Http\Requests\Admin\UpdateProveedorRequest;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Proveedor::class, 'proveedor');
    }

    public function index(Request $request): View
    {
        $proveedores = Proveedor::query()
            ->when($request->filled('buscar'), fn ($query) => $query->buscar((string) $request->input('buscar')))
            ->when($request->filled('tipo'), fn ($query) => $query->where('tipo', $request->input('tipo')))
            ->when($request->filled('estado'), fn ($query) => $query->where(
                'activo',
                $request->input('estado') === 'activos'
            ))
            ->withCount('ordenesCompra')
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('admin.proveedores.index', compact('proveedores'));
    }

    public function create(): View
    {
        return view('admin.proveedores.create');
    }

    public function store(StoreProveedorRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            Proveedor::create($request->validated());
        });

        return redirect()
            ->route('admin.proveedores.index')
            ->with('success', 'Proveedor creado exitosamente.');
    }

    public function show(Proveedor $proveedor): View
    {
        $proveedor->loadCount('ordenesCompra');

        return view('admin.proveedores.show', compact('proveedor'));
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('admin.proveedores.edit', compact('proveedor'));
    }

    public function update(UpdateProveedorRequest $request, Proveedor $proveedor): RedirectResponse
    {
        DB::transaction(function () use ($request, $proveedor) {
            $proveedor->update($request->validated());
        });

        return redirect()
            ->route('admin.proveedores.index')
            ->with('success', 'Proveedor actualizado exitosamente.');
    }

    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        if ($proveedor->tieneDependencias()) {
            return redirect()
                ->route('admin.proveedores.index')
                ->with('error', 'No se puede eliminar un proveedor con órdenes de compra asociadas.');
        }

        $proveedor->delete();

        return redirect()
            ->route('admin.proveedores.index')
            ->with('success', 'Proveedor eliminado exitosamente.');
    }
}
