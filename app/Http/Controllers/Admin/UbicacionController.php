<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUbicacionRequest;
use App\Http\Requests\Admin\UpdateUbicacionRequest;
use App\Models\Ubicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UbicacionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Ubicacion::class, 'ubicacion');
    }

    public function index(): View
    {
        $ubicaciones = Ubicacion::query()
            ->withCount('stockUbicaciones')
            ->orderByRaw("CASE WHEN tipo = 'tienda' THEN 0 ELSE 1 END")
            ->orderBy('pasillo')
            ->orderBy('estante')
            ->orderBy('anaquel')
            ->orderBy('nombre')
            ->get();

        return view('admin.ubicaciones.index', compact('ubicaciones'));
    }

    public function create(): View
    {
        return view('admin.ubicaciones.create');
    }

    public function store(StoreUbicacionRequest $request): RedirectResponse
    {
        Ubicacion::create($request->validated());

        return redirect()
            ->route('admin.ubicaciones.index')
            ->with('success', 'Ubicación creada exitosamente.');
    }

    public function show(Ubicacion $ubicacion): RedirectResponse
    {
        return redirect()->route('admin.ubicaciones.edit', $ubicacion);
    }

    public function edit(Ubicacion $ubicacion): View
    {
        return view('admin.ubicaciones.edit', compact('ubicacion'));
    }

    public function update(UpdateUbicacionRequest $request, Ubicacion $ubicacion): RedirectResponse
    {
        $ubicacion->update($request->validated());

        return redirect()
            ->route('admin.ubicaciones.index')
            ->with('success', 'Ubicación actualizada exitosamente.');
    }

    public function destroy(Ubicacion $ubicacion): RedirectResponse
    {
        if ($ubicacion->stockUbicaciones()->where('cantidad', '>', 0)->exists()) {
            return redirect()
                ->route('admin.ubicaciones.index')
                ->with('error', 'No se puede eliminar: la ubicación todavía tiene stock asignado.');
        }

        $ubicacion->delete();

        return redirect()
            ->route('admin.ubicaciones.index')
            ->with('success', 'Ubicación eliminada exitosamente.');
    }
}
