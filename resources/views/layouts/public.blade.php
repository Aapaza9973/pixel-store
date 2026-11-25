<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('title')@yield('title') — @endif Pixel Store</title>

    <meta name="description" content="@yield('description', 'Componentes, almacenamiento y periféricos en La Paz, Bolivia. Stock real, precios en bolivianos y la ficha técnica a la vista.')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <meta property="og:site_name" content="Pixel Store">
    <meta property="og:locale" content="es_BO">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@hasSection('title')@yield('title') — @endif Pixel Store">
    <meta property="og:description" content="@yield('description', 'Componentes, almacenamiento y periféricos en La Paz, Bolivia. Stock real, precios en bolivianos y la ficha técnica a la vista.')">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:image" content="@yield('og_image', asset('images/logo/pixel-logo-horizontal.png'))">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|geist:400,500,600|jetbrains-mono:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="min-h-screen bg-surface font-body-md text-body-md text-on-surface antialiased" x-data="carritoPublico()">

    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-obsidian-lg focus:bg-primary-container focus:px-4 focus:py-2 focus:font-label-md focus:text-label-md focus:uppercase focus:tracking-[0.14em] focus:text-on-primary-container">
        Ir al contenido
    </a>

    {{-- ============ HEADER PÚBLICO ============ --}}
    <header class="sticky top-0 z-40 border-b border-outline-variant/30 bg-surface/85 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 sm:px-6">

            <a href="{{ route('home') }}" class="flex flex-shrink-0 items-center gap-2.5">
                <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                     alt="Pixel Store"
                     class="h-9 w-9 rounded-obsidian">
                <span class="hidden flex-col leading-none sm:flex">
                    <span class="font-headline-sm text-headline-sm tracking-tight text-on-surface">PIXEL STORE</span>
                    <span class="mt-1 font-label-sm text-label-sm tracking-[0.18em] text-outline">TECNOLOGÍA SERIA</span>
                </span>
            </a>

            <nav class="hidden items-center gap-6 font-body-sm text-body-sm text-on-surface-variant md:flex" aria-label="Navegación principal">
                <a href="{{ route('catalogo.index') }}" class="transition hover:text-on-surface">Catálogo</a>
                <a href="{{ route('catalogo.favoritos.index') }}"
                   data-favoritos-contador="{{ $favoritosCount ?? 0 }}"
                   title="Favoritos: {{ $favoritosCount ?? 0 }}"
                   class="inline-flex items-center gap-1.5 transition hover:text-on-surface">
                    Favoritos
                    <span class="min-w-[1.25rem] rounded-obsidian bg-surface-container px-1 text-center font-label-sm text-label-sm tabular-nums text-on-surface-variant">{{ $favoritosCount ?? 0 }}</span>
                </a>
            </nav>

            <form method="GET" action="{{ route('catalogo.index') }}" role="search"
                  class="ml-auto hidden max-w-xs flex-1 items-center md:flex">
                <label for="buscar-header" class="sr-only">Buscar productos</label>
                <div class="relative w-full">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-outline"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input id="buscar-header" name="buscar" type="search"
                           placeholder="Buscar producto o SKU"
                           class="w-full rounded-obsidian-lg border border-outline-variant/40 bg-surface-container-low py-2 pl-9 pr-3 font-body-sm text-body-sm text-on-surface placeholder:text-outline transition focus:border-primary-container focus:outline-none focus:ring-2 focus:ring-primary-container/25">
                </div>
            </form>

            <button type="button" x-on:click="abierto = true"
                    class="relative ml-auto inline-flex items-center gap-2 rounded-obsidian-lg border border-outline-variant/40 px-3 py-2 font-label-sm text-label-sm uppercase tracking-[0.14em] text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface md:ml-0"
                    x-bind:aria-label="`Carrito: ${items.length} artículos`">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 005.6 19H19M9 22a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/>
                </svg>
                <span>Carrito</span>
                <span x-show="items.length > 0" x-cloak
                      class="rounded-obsidian bg-primary-container px-1.5 py-0.5 font-label-sm text-label-sm tabular-nums text-on-primary-container"
                      x-text="items.reduce((n, i) => n + i.cantidad, 0)"></span>
            </button>
        </div>
    </header>

    <main id="contenido" tabindex="-1" class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6">
        @yield('content')
    </main>

    {{-- ============ FOOTER PÚBLICO ============ --}}
    <footer class="mt-16 border-t border-outline-variant/30">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                     alt="Pixel Store"
                     class="h-8 w-8 rounded-obsidian">
                <span class="flex flex-col leading-none">
                    <span class="font-headline-sm text-headline-sm tracking-tight text-on-surface">PIXEL STORE</span>
                    <span class="mt-1 font-label-sm text-label-sm tracking-[0.18em] text-outline">TODO EL MUNDO TECNOLÓGICO, PIXEL A PIXEL</span>
                </span>
            </div>

            <nav class="flex flex-col gap-2 font-body-sm text-body-sm text-on-surface-variant" aria-label="Enlaces del sitio">
                <a href="{{ route('catalogo.index') }}" class="transition hover:text-on-surface">Catálogo completo</a>
                <a href="{{ route('catalogo.favoritos.index') }}" class="transition hover:text-on-surface">Mis favoritos</a>
                <a href="{{ route('catalogo.checkout') }}" class="transition hover:text-on-surface">Finalizar compra</a>
            </nav>

            <div class="space-y-2 font-label-sm text-label-sm text-outline">
                <p>La Paz, Bolivia</p>
                <p>Pedidos en línea con stock real</p>
                <a href="{{ route('login') }}" class="inline-block uppercase tracking-[0.18em] transition hover:text-on-surface-variant">Acceso staff</a>
            </div>
        </div>

        <div class="border-t border-outline-variant/20">
            <p class="mx-auto max-w-6xl px-4 py-4 font-label-sm text-label-sm text-outline sm:px-6">
                &copy; {{ date('Y') }} Pixel Store · Precios en bolivianos (Bs)
            </p>
        </div>
    </footer>

    {{-- ============ CARRITO (Alpine + localStorage) ============ --}}
    <div x-cloak x-show="abierto" x-on:keydown.escape.window="abierto = false"
         class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Carrito de compras">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" x-on:click="abierto = false"></div>

        <aside class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col border-l border-outline-variant/40 bg-surface-container-lowest shadow-2xl"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-150"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="translate-x-full">

            <header class="flex items-center justify-between border-b border-outline-variant/30 px-5 py-4">
                <div>
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Pixel Store</p>
                    <h2 class="mt-0.5 font-headline-sm text-headline-sm text-on-surface">Carrito</h2>
                </div>
                <button type="button" x-on:click="abierto = false" aria-label="Cerrar carrito"
                        class="rounded-obsidian border border-outline-variant/40 p-1.5 text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </header>

            <template x-if="items.length === 0">
                <div class="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center">
                    <p class="font-body-md text-body-md text-on-surface-variant">Tu carrito está vacío.</p>
                    <a href="{{ route('catalogo.index') }}" x-on:click="abierto = false"
                       class="rounded-obsidian-lg border border-outline-variant/40 px-4 py-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
                        Ver catálogo
                    </a>
                </div>
            </template>

            <template x-if="items.length > 0">
                <ul class="flex-1 divide-y divide-outline-variant/15 overflow-y-auto">
                    <template x-for="(item, index) in items" :key="item.producto_id">
                        <li class="flex gap-3 p-4">
                            <template x-if="item.imagen">
                                <img x-bind:src="item.imagen" x-bind:alt="item.nombre"
                                     class="h-16 w-16 flex-shrink-0 rounded-obsidian object-cover">
                            </template>
                            <template x-if="! item.imagen">
                                <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-obsidian bg-surface-container-low">
                                    <span class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">—</span>
                                </div>
                            </template>

                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-2 font-body-sm text-body-sm text-on-surface" x-text="item.nombre"></p>
                                <p class="mt-0.5 font-label-md text-label-md tabular-nums text-on-surface-variant" x-text="formato(item.precio)"></p>

                                <div class="mt-2 flex items-center gap-2">
                                    <button type="button" x-on:click="cambiarCantidad(index, -1)" aria-label="Quitar una unidad"
                                            class="h-7 w-7 rounded-obsidian border border-outline-variant/40 font-label-md text-label-md text-on-surface transition hover:border-primary-container/50">−</button>
                                    <span class="min-w-[2rem] text-center font-label-md text-label-md tabular-nums text-on-surface" x-text="item.cantidad"></span>
                                    <button type="button" x-on:click="cambiarCantidad(index, 1)"
                                            x-bind:disabled="item.cantidad >= item.stock" aria-label="Agregar una unidad"
                                            class="h-7 w-7 rounded-obsidian border border-outline-variant/40 font-label-md text-label-md text-on-surface transition hover:border-primary-container/50 disabled:cursor-not-allowed disabled:opacity-40">+</button>

                                    <button type="button" x-on:click="quitar(index)"
                                            class="ml-auto font-label-sm text-label-sm uppercase tracking-[0.14em] text-error transition hover:text-error/80">
                                        Quitar
                                    </button>
                                </div>
                            </div>

                            <p class="font-label-md text-label-md tabular-nums text-on-surface" x-text="formato(item.precio * item.cantidad)"></p>
                        </li>
                    </template>
                </ul>
            </template>

            <footer class="space-y-3 border-t border-outline-variant/30 p-5">
                <div class="flex items-baseline justify-between">
                    <span class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Subtotal</span>
                    <span class="font-headline-sm text-headline-sm tabular-nums text-on-surface" x-text="formato(subtotal)"></span>
                </div>

                <a href="{{ route('catalogo.checkout') }}"
                   x-bind:class="items.length === 0 ? 'pointer-events-none opacity-40' : ''"
                   x-bind:aria-disabled="items.length === 0"
                   class="flex w-full items-center justify-center rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                    Ir a pagar
                </a>

                <button type="button" x-on:click="vaciar()" x-show="items.length > 0" x-cloak
                        class="w-full font-label-sm text-label-sm uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-error">
                    Vaciar carrito
                </button>
            </footer>
        </aside>
    </div>

    @stack('scripts')
</body>
</html>
