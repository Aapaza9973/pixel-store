@extends('layouts.app')

@section('title', 'Registrar Serie')
@section('subtitle', 'Alta de un número de serie en inventario')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.series.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <h2 class="mb-4 font-headline-sm text-headline-sm text-on-surface">Datos de la serie</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="producto_id" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto *</label>
                    <select name="producto_id" id="producto_id" required
                            class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                        <option value="">Seleccione un producto</option>
                        @foreach ($productos as $producto)
                            <option value="{{ $producto->id }}" {{ (string) old('producto_id') === (string) $producto->id ? 'selected' : '' }}>
                                {{ $producto->nombre }}{{ $producto->sku ? ' · '.$producto->sku : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('producto_id')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="numero_serie" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Número de serie *</label>
                    <input type="text" name="numero_serie" id="numero_serie" value="{{ old('numero_serie') }}" required
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-mono text-body-md text-on-surface">
                    @error('numero_serie')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="costo_unitario" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Costo unitario</label>
                    <input type="number" step="0.01" min="0" name="costo_unitario" id="costo_unitario"
                           value="{{ old('costo_unitario') }}"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    @error('costo_unitario')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="fecha_ingreso" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Fecha de ingreso</label>
                    <input type="date" name="fecha_ingreso" id="fecha_ingreso"
                           value="{{ old('fecha_ingreso', now()->toDateString()) }}"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    @error('fecha_ingreso')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ubicacion_id" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ubicación</label>
                    <select name="ubicacion_id" id="ubicacion_id"
                            class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                        <option value="">Sin ubicación</option>
                        @foreach ($ubicaciones as $ubicacion)
                            <option value="{{ $ubicacion->id }}" {{ (string) old('ubicacion_id') === (string) $ubicacion->id ? 'selected' : '' }}>
                                {{ $ubicacion->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('ubicacion_id')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="notas" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Notas</label>
                    <textarea name="notas" id="notas" rows="3"
                              class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">{{ old('notas') }}</textarea>
                    @error('notas')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                Guardar serie
            </button>
            <a href="{{ route('admin.series.index') }}"
               class="rounded-obsidian-lg bg-surface-container px-4 py-2 font-body-md text-body-md text-on-surface-variant transition hover:text-on-surface">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
