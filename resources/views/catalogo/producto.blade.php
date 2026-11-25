@extends('layouts.public')

@php
    $imagenSeo = $producto->imagenesGaleria->firstWhere('es_principal', true)
        ?? $producto->imagenesGaleria->first();

    $disponibleSeo = (int) $producto->stock > 0;
@endphp

@section('title', $producto->nombre)
@section('description', \Illuminate\Support\Str::limit(trim(strip_tags((string) $producto->descripcion)), 140) ?: 'Ficha técnica, stock y precio de '.$producto->nombre.' en Pixel Store, La Paz.')
@section('og_type', 'product')
@section('og_image', $imagenSeo?->url() ?? asset('images/logo/pixel-logo-horizontal.png'))

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
        <a href="{{ route('home') }}" class="transition hover:text-on-surface">Inicio</a>
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
                    {{ \App\Support\Dinero::formatear((float) $producto->precio_unitario) }}
                </p>
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                    Stock disponible: {{ (int) $producto->stock }} u.
                </p>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    @if ((int) $producto->stock > 0)
                        <button type="button"
                                x-on:click="agregar({
                                    producto_id: {{ (int) $producto->id }},
                                    nombre: @js($producto->nombre),
                                    precio: {{ (float) $producto->precio_unitario }},
                                    imagen: @js($principal?->urlThumbnail()),
                                    stock: {{ (int) $producto->stock }},
                                    slug: @js($producto->slug),
                                })"
                                class="inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-4 py-2.5 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60">
                            Agregar al carrito
                            <span x-show="cantidadDe({{ (int) $producto->id }}) > 0" x-cloak
                                  x-text="cantidadDe({{ (int) $producto->id }})"
                                  class="min-w-[1.25rem] rounded-full bg-black/20 px-1.5 py-0.5 text-center font-label-sm text-label-sm tabular-nums"></span>
                        </button>
                    @else
                        <button type="button" disabled
                                class="inline-flex cursor-not-allowed items-center gap-2 rounded-obsidian-lg border border-outline-variant/40 px-4 py-2.5 text-body-md font-medium text-outline">
                            Agotado
                        </button>
                    @endif

                    <a href="mailto:ventas@pixelstore.com?subject={{ rawurlencode('Consulta: '.$producto->nombre) }}"
                       class="inline-flex items-center gap-2 rounded-obsidian-lg border border-outline-variant/40 px-4 py-2.5 text-body-md font-medium text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
                        Consultar disponibilidad
                    </a>

                    @include('catalogo._boton-favorito', [
                        'producto' => $producto,
                        'favorito' => in_array((int) $producto->id, $favoritosIds, true),
                    ])
                </div>
            </div>

            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface">Descripción</h2>
                <p class="mt-2 font-body-md text-body-md text-on-surface-variant">
                    {{ $producto->descripcion ?? 'Este producto todavía no tiene descripción.' }}
                </p>
            </div>

            {{-- Especificaciones: atributos EAV cargados por el controlador. --}}
            @php
                $especificaciones = $producto->atributos
                    ->filter(fn ($atributo) => $atributo->atributo !== null
                        && $atributo->valor !== null
                        && $atributo->valor !== '')
                    ->sortBy(fn ($atributo) => [$atributo->atributo->orden, $atributo->atributo->nombre]);
            @endphp

            @if ($especificaciones->isNotEmpty())
                <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
                    <h2 class="font-headline-sm text-headline-sm text-on-surface">Especificaciones técnicas</h2>

                    <dl class="mt-3 divide-y divide-outline-variant/20">
                        @foreach ($especificaciones as $especificacion)
                            <div class="flex items-baseline justify-between gap-4 py-2">
                                <dt class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">
                                    {{ str_replace('_', ' ', $especificacion->atributo->nombre) }}{{ $especificacion->atributo->unidad ? ' ('.$especificacion->atributo->unidad.')' : '' }}
                                </dt>
                                <dd class="text-right font-body-sm text-body-sm text-on-surface">
                                    {{ $especificacion->valor_legible }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif
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

@push('head')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $producto->nombre,
            'description' => \Illuminate\Support\Str::limit(trim(strip_tags((string) $producto->descripcion)), 300) ?: null,
            'sku' => $producto->sku ?: 'PS-'.$producto->id,
            'image' => $imagenSeo?->url(),
            'brand' => $producto->marca?->nombre ? ['@type' => 'Brand', 'name' => $producto->marca->nombre] : null,
            'category' => $producto->categoria?->nombre,
            'offers' => [
                '@type' => 'Offer',
                'url' => route('catalogo.producto', $producto->slug),
                'priceCurrency' => 'BOB',
                'price' => number_format((float) $producto->precio_unitario, 2, '.', ''),
                'availability' => $disponibleSeo ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ], static fn ($valor) => $valor !== null), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
