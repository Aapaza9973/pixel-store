@extends('layouts.app')

@section('title', 'Catálogo')
@section('subtitle', 'Catálogo público')

@section('content')
@php
    $ordenActual = $filtros['orden'] ?? 'novedades';

    $hayFiltros = filled($filtros['buscar'] ?? null)
        || filled($filtros['categoria_id'] ?? null)
        || filled($filtros['marca_id'] ?? null)
        || $ordenActual !== 'novedades';

    $inputClass = 'mt-1.5 w-full rounded-obsidian border border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-sm text-body-sm text-on-surface transition focus:border-primary-container focus:outline-none focus:ring-2 focus:ring-primary-container/25';
    $labelClass = 'font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline';
@endphp

<div class="mx-auto max-w-6xl space-y-6">

    {{-- Migas --}}
    <nav class="flex items-center gap-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
        <a href="{{ url('/') }}" class="transition hover:text-on-surface">Inicio</a>
        <span>/</span>
        <span class="text-on-surface">Catálogo</span>
    </nav>

    {{-- Encabezado --}}
    <header class="flex flex-wrap items-end justify-between gap-4 border-b border-outline-variant/30 pb-4">
        <div>
            <p class="{{ $labelClass }}">Pixel Store</p>
            <h1 class="mt-1 font-headline-lg text-headline-lg text-on-surface">Catálogo</h1>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                {{ $productos->total() }} {{ $productos->total() === 1 ? 'producto disponible' : 'productos disponibles' }}@if (filled($filtros['buscar'] ?? null)) para «{{ $filtros['buscar'] }}»@endif
            </p>
        </div>

        @if ($hayFiltros)
            <a href="{{ route('catalogo.index') }}"
               class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-secondary transition hover:text-on-surface">
                Limpiar filtros
            </a>
        @endif
    </header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[240px_1fr]">

        {{-- Filtros --}}
        <aside class="lg:sticky lg:top-0 lg:self-start">
            <form method="GET" action="{{ route('catalogo.index') }}"
                  class="space-y-4 rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-4 backdrop-blur-xl">

                <div>
                    <label for="buscar" class="{{ $labelClass }}">Buscar</label>
                    <input id="buscar" name="buscar" type="search"
                           value="{{ $filtros['buscar'] ?? '' }}"
                           placeholder="Nombre o SKU"
                           class="{{ $inputClass }} placeholder:text-outline">
                </div>

                <div>
                    <label for="categoria_id" class="{{ $labelClass }}">Categoría</label>
                    <select id="categoria_id" name="categoria_id" class="{{ $inputClass }}">
                        <option value="">Todas</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}"
                                @selected((string) ($filtros['categoria_id'] ?? '') === (string) $categoria->id)>
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="marca_id" class="{{ $labelClass }}">Marca</label>
                    <select id="marca_id" name="marca_id" class="{{ $inputClass }}">
                        <option value="">Todas</option>
                        @foreach ($marcas as $marca)
                            <option value="{{ $marca->id }}"
                                @selected((string) ($filtros['marca_id'] ?? '') === (string) $marca->id)>
                                {{ $marca->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="orden" class="{{ $labelClass }}">Orden</label>
                    <select id="orden" name="orden" class="{{ $inputClass }}">
                        <option value="novedades" @selected($ordenActual === 'novedades')>Novedades</option>
                        <option value="precio_asc" @selected($ordenActual === 'precio_asc')>Menor precio</option>
                        <option value="precio_desc" @selected($ordenActual === 'precio_desc')>Mayor precio</option>
                        <option value="nombre_asc" @selected($ordenActual === 'nombre_asc')>Nombre A–Z</option>
                        <option value="nombre_desc" @selected($ordenActual === 'nombre_desc')>Nombre Z–A</option>
                    </select>
                </div>

                <button type="submit"
                        class="w-full rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60">
                    Aplicar filtros
                </button>
            </form>
        </aside>

        {{-- Grilla --}}
        <section class="space-y-6">
            @if ($productos->isEmpty())
                <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-10 text-center backdrop-blur-xl">
                    <p class="font-headline-sm text-headline-sm text-on-surface">No encontramos productos</p>
                    <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                        Probá con otra búsqueda o quitá los filtros.
                    </p>
                    <a href="{{ route('catalogo.index') }}"
                       class="mt-4 inline-flex items-center rounded-obsidian-lg border border-outline-variant/40 px-4 py-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
                        Ver todo el catálogo
                    </a>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach ($productos as $producto)
                        @include('catalogo._card-producto', [
                            'producto' => $producto,
                            'favorito' => false,
                            'favoritosIds' => $favoritosIds,
                        ])
                    @endforeach
                </div>

                <div class="border-t border-outline-variant/30 pt-4">
                    {{ $productos->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
