@extends('layouts.app')

@section('title', $producto->nombre)
@section('subtitle', 'Catálogo público')

@section('content')
@php
    $imagenes = $producto->imagenesGaleria;

    $principal = $imagenes->firstWhere('es_principal', true) ?? $imagenes->first();

    $estadoStock = match (true) {
        (int) $producto->stock <= 0 => ['label' => 'Agotado', 'clase' => 'border-error/60 bg-error/10 text-error'],
        (int) $producto->stock <= (int) $producto->umbral_alerta => ['label' => 'Últimas unidades', 'clase' => 'border-amber-500/25 bg-amber-500/10 text-amber-400'],
        default => ['label' => 'Disponible', 'clase' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400'],
    };
@endphp

<div class="mx-auto max-w-5xl space-y-8"
     x-data="{ activa: '{{ $principal?->path ?? '' }}' }">

    {{-- Migas --}}
    <nav class="flex items-center gap-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
        <a href="{{ url('/') }}" class="transition hover:text-on-surface">Inicio</a>
        <span>/</span>
        <span class="text-on-surface-variant">{{ $producto->categoria?->nombre ?? 'Sin categoría' }}</span>
        <span>/</span>
        <span class="text-on-surface">{{ $producto->nombre }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">

        {{-- Galería --}}
        <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
            @if ($principal)
                <template x-if="activa">
                    <img x-bind:src="'{{ asset('storage') }}/' + activa"
                         alt="{{ $principal->alt ?? $producto->nombre }}"
                         class="aspect-square w-full object-cover">
                </template>

                <template x-if="! activa">
                    <div class="flex aspect-square w-full items-center justify-center bg-surface-container-low">
                        <span class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Sin imagen</span>
                    </div>
                </template>
            @else
                <div class="flex aspect-square w-full items-center justify-center bg-surface-container-low">
                    <div class="flex flex-col items-center gap-3 text-outline">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 4h16v16H4z"/>
                        </svg>
                        <span class="font-label-sm text-label-sm uppercase tracking-[0.18em]">
                            Sin imagen
                        </span>
                    </div>
                </div>
            @endif

            @if ($imagenes->count() > 1)
                <div class="grid grid-cols-4 gap-2 border-t border-outline-variant/30 p-3">
                    @foreach ($imagenes as $miniatura)
                        <button type="button"
                                x-on:click="activa = '{{ $miniatura->path }}'"
                                x-bind:class="activa === '{{ $miniatura->path }}' ? 'border-primary-container' : 'border-outline-variant/30'"
                                class="overflow-hidden rounded-obsidian border transition hover:border-primary-container/60"
                                aria-label="Ver imagen {{ $loop->iteration }}">
                            <img src="{{ $miniatura->urlThumbnail() }}"
                                 alt="{{ $miniatura->alt ?? $producto->nombre }}"
                                 class="aspect-square w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Ficha --}}
        <div class="space-y-6">
            <div>
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    {{ $producto->marca?->nombre ?? 'Pixel Store' }}
                </p>
                <h1 class="mt-2 font-headline-lg text-headline-lg text-on-surface">
                    {{ $producto->nombre }}
                </h1>

                <div class="mt-4 flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] {{ $estadoStock['clase'] }}">
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                        {{ $estadoStock['label'] }}
                    </span>

                    @if ($producto->sku)
                        <span class="font-label-md text-label-md text-outline">SKU {{ $producto->sku }}</span>
                    @endif
                </div>
            </div>

            <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Precio</p>
                <p class="mt-1 font-headline-lg text-headline-lg tabular-nums text-on-surface">
                    Bs {{ number_format((float) $producto->precio_unitario, 2, ',', '.') }}
                </p>
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                    Stock disponible: {{ (int) $producto->stock }} u.
                </p>

                <a href="mailto:ventas@pixelstore.com?subject={{ rawurlencode('Consulta: '.$producto->nombre) }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-4 py-2.5 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                    Consultar disponibilidad
                </a>
            </div>

            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface">Descripción</h2>
                <p class="mt-2 font-body-md text-body-md text-on-surface-variant">
                    {{ $producto->descripcion ?? 'Este producto todavía no tiene descripción.' }}
                </p>
            </div>

            {{-- Especificaciones: se completan en HU-2.4 --}}
            <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
                <h2 class="font-headline-sm text-headline-sm text-on-surface">Especificaciones técnicas</h2>
                <p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">
                    La tabla de especificaciones se publica cuando el catálogo técnico esté completo (HU-2.4).
                </p>
            </div>
        </div>
    </div>

    @if ($similares->isNotEmpty())
        <section class="border-t border-outline-variant/30 pt-8">
            <h2 class="font-headline-md text-headline-md text-on-surface">Productos similares</h2>

            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ($similares as $similar)
                    @include('catalogo._card-producto', ['producto' => $similar])
                @endforeach
            </div>
        </section>
    @endif

    @if ($alternativas->isNotEmpty())
        <section class="border-t border-outline-variant/30 pt-8">
            <h2 class="font-headline-md text-headline-md text-on-surface">Alternativas</h2>

            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ($alternativas as $alternativa)
                    @include('catalogo._card-producto', ['producto' => $alternativa])
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
