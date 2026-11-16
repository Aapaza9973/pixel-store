@extends('layouts.app')

@section('title', 'Editar Serie')
@section('subtitle', 'Estado, garantía y ubicación de la serie')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.series.update', $serie) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="font-headline-sm text-headline-sm text-on-surface">{{ $serie->numero_serie }}</h2>
                <span class="font-label-md text-label-md text-outline">{{ $serie->producto?->nombre }}</span>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="estado" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado *</label>
                    <select name="estado" id="estado" required
                            class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                        @foreach ($estados as $opcion)
                            <option value="{{ $opcion }}" {{ old('estado', $serie->estado) === $opcion ? 'selected' : '' }}>{{ ucfirst($opcion) }}</option>
                        @endforeach
                    </select>
                    @error('estado')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="costo_unitario" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Costo unitario</label>
                    <input type="number" step="0.01" min="0" name="costo_unitario" id="costo_unitario"
                           value="{{ old('costo_unitario', $serie->costo_unitario) }}"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    @error('costo_unitario')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="garantia_hasta" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Garantía hasta</label>
                    <input type="date" name="garantia_hasta" id="garantia_hasta"
                           value="{{ old('garantia_hasta', $serie->garantia_hasta?->toDateString()) }}"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    @error('garantia_hasta')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="ubicacion_id" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ubicación</label>
                    <select name="ubicacion_id" id="ubicacion_id"
                            class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                        <option value="">Sin ubicación</option>
                        @foreach ($ubicaciones as $ubicacion)
                            <option value="{{ $ubicacion->id }}" {{ (string) old('ubicacion_id', $serie->ubicacion_id) === (string) $ubicacion->id ? 'selected' : '' }}>
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
                              class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">{{ old('notas', $serie->notas) }}</textarea>
                    @error('notas')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                Guardar cambios
            </button>
            <a href="{{ route('admin.series.show', $serie) }}"
               class="rounded-obsidian-lg bg-surface-container px-4 py-2 font-body-md text-body-md text-on-surface-variant transition hover:text-on-surface">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
