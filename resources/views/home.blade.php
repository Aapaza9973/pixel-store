@extends('layouts.public')

@section('title', 'Hardware con la ficha técnica a la vista')
@section('description', 'Componentes, almacenamiento y periféricos en La Paz, Bolivia. '.$totalProductos.' productos con stock real y precios en bolivianos.')

@section('content')
    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden rounded-obsidian-xl border border-outline-variant/30">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-primary-container/10 to-transparent"></div>

        <div class="relative grid gap-10 px-6 py-12 lg:grid-cols-2 lg:items-center lg:py-16">

            <div>
                <p class="font-label-md text-label-md uppercase tracking-[0.16em] text-primary">
                    // Hardware de escritorio · La Paz, Bolivia
                </p>

                <h1 class="mt-4 font-headline-lg text-headline-lg tracking-tight text-on-surface sm:text-headline-xl">
                    Cada componente, con su <span class="text-primary">ficha técnica</span> a la vista.
                </h1>

                <p class="mt-5 max-w-xl font-body-lg text-body-lg leading-relaxed text-on-surface-variant">
                    Componentes, almacenamiento y periféricos para armar tu equipo.
                    Stock real en tienda, precios en bolivianos y las especificaciones completas. Sin letra chica.
                </p>

                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <a href="{{ route('catalogo.index') }}"
                       class="inline-flex items-center rounded-obsidian-lg bg-primary-container px-6 py-3 font-label-lg text-label-lg text-on-primary-container transition hover:bg-primary-container/90">
                        Ver catálogo
                    </a>
                    <a href="{{ route('catalogo.checkout') }}"
                       class="inline-flex items-center rounded-obsidian-lg border border-outline-variant/40 px-6 py-3 font-label-lg text-label-lg text-on-surface transition hover:border-primary-container/50">
                        Finalizar pedido
                    </a>
                </div>

                <p class="mt-5 font-label-sm text-label-sm text-outline">
                    {{ $totalProductos }} productos disponibles · pedidos en línea sin crear cuenta
                </p>
            </div>

            <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-low p-6">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.16em] text-primary">// Cómo comprar</p>

                <ol class="mt-4 space-y-3 font-body-sm text-body-sm text-on-surface-variant">
                    <li class="flex gap-3">
                        <span class="font-label-md text-label-md tabular-nums text-outline">01</span>
                        <span>Elegí los productos y agregalos al carrito. Ves el stock real de la tienda.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="font-label-md text-label-md tabular-nums text-outline">02</span>
                        <span>Completá tus datos de entrega. No hace falta crear una cuenta.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="font-label-md text-label-md tabular-nums text-outline">03</span>
                        <span>Recibís el número de pedido y coordinamos el pago y la entrega.</span>
                    </li>
                </ol>
            </div>
        </div>
    </section>

    {{-- ============ CATEGORÍAS ============ --}}
    @if ($categorias->isNotEmpty())
        <section class="mt-12">
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Explorar por categoría</h2>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($categorias as $categoria)
                    <a href="{{ route('catalogo.index', ['categoria_id' => $categoria->id]) }}"
                       class="inline-flex items-center gap-2 rounded-obsidian-lg border border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-sm text-body-sm text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
                        {{ $categoria->nombre }}
                        <span class="font-label-sm text-label-sm tabular-nums text-outline">{{ $categoria->productos_count }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============ DESTACADOS ============ --}}
    <section class="mt-12">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="font-label-md text-label-md uppercase tracking-[0.16em] text-primary">// En stock hoy</p>
                <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">Productos destacados</h2>
            </div>
            <a href="{{ route('catalogo.index') }}"
               class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-secondary transition hover:text-on-surface">
                Ver todo el catálogo
            </a>
        </div>

        @if ($destacados->isEmpty())
            <p class="mt-6 rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-low p-6 font-body-md text-body-md text-on-surface-variant">
                Todavía no hay productos publicados en el catálogo.
            </p>
        @else
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($destacados as $producto)
                    @include('catalogo._card-producto', ['producto' => $producto])
                @endforeach
            </div>
        @endif
    </section>
@endsection

@push('head')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => 'Pixel Store',
            'description' => 'Componentes, almacenamiento y periféricos para armar tu equipo.',
            'url' => route('home'),
            'image' => asset('images/logo/pixel-logo-horizontal.png'),
            'priceRange' => 'Bs',
            'currenciesAccepted' => 'BOB',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'La Paz',
                'addressCountry' => 'BO',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
