@extends('layouts.public')

@section('title', 'Catálogo')

@section('content')
@php
    $ordenActual = $filtros['orden'] ?? 'novedades';

    $hayFiltros = filled($filtros['buscar'] ?? null)
        || filled($filtros['categoria_id'] ?? null)
        || filled($filtros['marca_id'] ?? null)
        || filled($filtros['precio_min'] ?? null)
        || filled($filtros['precio_max'] ?? null)
        || ! empty($atributosAplicados)
        || $ordenActual !== 'novedades';

    $inputClass = 'w-full rounded-obsidian border border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-sm text-body-sm text-on-surface transition focus:border-primary-container focus:outline-none focus:ring-2 focus:ring-primary-container/25';
    $labelClass = 'font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline';
@endphp

<div class="mx-auto max-w-6xl space-y-6">

    {{-- Migas --}}
    <nav class="flex items-center gap-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
        <a href="{{ route('home') }}" class="transition hover:text-on-surface">Inicio</a>
        <span>/</span>
        <span class="text-on-surface">Catálogo</span>
    </nav>

    <header class="flex flex-wrap items-end justify-between gap-4 border-b border-outline-variant/30 pb-4">
        <div>
            <p class="{{ $labelClass }}">Pixel Store</p>
            <h1 class="mt-1 font-headline-lg text-headline-lg text-on-surface">Catálogo</h1>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                Componentes, periféricos y equipos con stock disponible.
            </p>
        </div>

        @if ($hayFiltros)
            <a href="{{ route('catalogo.index') }}"
               class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-secondary transition hover:text-on-surface">
                Limpiar filtros
            </a>
        @endif
    </header>

    <form method="GET" action="{{ route('catalogo.index') }}" class="space-y-5">

        {{-- Buscador --}}
        <div class="flex flex-col gap-2 sm:flex-row">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-outline"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
                <label for="buscar" class="sr-only">Buscar productos</label>
                <input id="buscar" name="buscar" type="search"
                       value="{{ $filtros['buscar'] ?? '' }}"
                       placeholder="Buscar por nombre o SKU"
                       class="{{ $inputClass }} py-2.5 pl-10 pr-3 placeholder:text-outline">
            </div>

            <button type="submit"
                    class="rounded-obsidian-lg bg-primary-container px-5 py-2.5 font-body-md text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60">
                Buscar
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[240px_1fr]">

            {{-- Filtros --}}
            <aside class="space-y-4 rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-4 backdrop-blur-xl lg:sticky lg:top-0 lg:self-start">
                <div class="flex items-center justify-between">
                    <p class="font-label-md text-label-md uppercase tracking-[0.18em] text-on-surface-variant">Filtros</p>
                    @if ($hayFiltros)
                        <a href="{{ route('catalogo.index') }}"
                           class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-secondary transition hover:text-on-surface">
                            Limpiar
                        </a>
                    @endif
                </div>

                <div>
                    <label for="categoria_id" class="{{ $labelClass }}">Categoría</label>
                    <select id="categoria_id" name="categoria_id" class="{{ $inputClass }} mt-1.5">
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
                    <select id="marca_id" name="marca_id" class="{{ $inputClass }} mt-1.5">
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
                    <p class="{{ $labelClass }}">Precio (Bs)</p>
                    <div class="mt-1.5 grid grid-cols-2 gap-2">
                        <div>
                            <label for="precio_min" class="sr-only">Precio mínimo</label>
                            <input id="precio_min" name="precio_min" type="number" step="0.01" min="0"
                                   value="{{ $filtros['precio_min'] ?? '' }}"
                                   placeholder="Mín."
                                   class="{{ $inputClass }} tabular-nums placeholder:text-outline">
                        </div>
                        <div>
                            <label for="precio_max" class="sr-only">Precio máximo</label>
                            <input id="precio_max" name="precio_max" type="number" step="0.01" min="0"
                                   value="{{ $filtros['precio_max'] ?? '' }}"
                                   placeholder="Máx."
                                   class="{{ $inputClass }} tabular-nums placeholder:text-outline">
                        </div>
                    </div>
                </div>

                @if ($atributosFiltro->isNotEmpty())
                    <div class="space-y-3 border-t border-outline-variant/30 pt-3">
                        <p class="{{ $labelClass }}">Características</p>

                        @foreach ($atributosFiltro as $atributo)
                            @php
                                $seleccionado = (string) ($atributosAplicados[$atributo->id] ?? '');
                            @endphp

                            <div>
                                <label for="atributo-{{ $atributo->id }}"
                                       class="{{ $atributo->valoresPredefinidos->isNotEmpty() ? 'sr-only' : $labelClass }}">
                                    {{ $atributo->nombre }}{{ $atributo->unidad ? ' ('.$atributo->unidad.')' : '' }}
                                </label>

                                @if ($atributo->valoresPredefinidos->isNotEmpty())
                                    <select id="atributo-{{ $atributo->id }}"
                                            name="atributos[{{ $atributo->id }}]"
                                            class="{{ $inputClass }}">
                                        <option value="">{{ $atributo->nombre }}: todos</option>
                                        @foreach ($atributo->valoresPredefinidos as $valorPredefinido)
                                            <option value="{{ $valorPredefinido->valor }}"
                                                @selected($seleccionado === (string) $valorPredefinido->valor)>
                                                {{ $valorPredefinido->valor }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input id="atributo-{{ $atributo->id }}"
                                           name="atributos[{{ $atributo->id }}]"
                                           type="text"
                                           value="{{ $seleccionado }}"
                                           class="{{ $inputClass }} mt-1.5">
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <button type="submit"
                        class="w-full rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60">
                    Aplicar
                </button>
            </aside>

            {{-- Resultados --}}
            <section class="space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/30 pb-3">
                    <p class="font-label-md text-label-md text-on-surface-variant">
                        Mostrando
                        <span class="tabular-nums text-on-surface">{{ $productos->total() }}</span>
                        {{ $productos->total() === 1 ? 'producto' : 'productos' }}
                        @if ($productos->lastPage() > 1)
                            <span class="text-outline">· página {{ $productos->currentPage() }} de {{ $productos->lastPage() }}</span>
                        @endif
                    </p>

                    <div class="flex items-center gap-2">
                        <label for="orden" class="{{ $labelClass }}">Orden</label>
                        <select id="orden" name="orden" onchange="this.form.submit()"
                                class="rounded-obsidian border border-outline-variant/40 bg-surface-container-low px-2.5 py-1.5 font-label-md text-label-md text-on-surface transition focus:border-primary-container focus:outline-none focus:ring-2 focus:ring-primary-container/25">
                            <option value="novedades" @selected($ordenActual === 'novedades')>Novedades</option>
                            <option value="precio_asc" @selected($ordenActual === 'precio_asc')>Menor precio</option>
                            <option value="precio_desc" @selected($ordenActual === 'precio_desc')>Mayor precio</option>
                            <option value="nombre_asc" @selected($ordenActual === 'nombre_asc')>Nombre A–Z</option>
                            <option value="nombre_desc" @selected($ordenActual === 'nombre_desc')>Nombre Z–A</option>
                        </select>
                    </div>
                </div>

                @if ($productos->isEmpty())
                    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-10 text-center backdrop-blur-xl">
                        <p class="font-headline-sm text-headline-sm text-on-surface">No encontramos productos con esos filtros.</p>
                        <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                            Probá con otra búsqueda o ampliá el rango de precio.
                        </p>
                        <a href="{{ route('catalogo.index') }}"
                           class="mt-4 inline-flex items-center rounded-obsidian-lg border border-outline-variant/40 px-4 py-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
                            Ver todo el catálogo
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($productos as $producto)
                            @include('catalogo._card-producto', [
                                'producto' => $producto,
                                'favorito' => in_array((int) $producto->id, $favoritosIds, true),
                            ])
                        @endforeach
                    </div>

                    <div class="border-t border-outline-variant/30 pt-4">
                        {{ $productos->links() }}
                    </div>
                @endif
            </section>
        </div>
    </form>
</div>
@endsection
