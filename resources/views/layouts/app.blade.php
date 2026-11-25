<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Título --}}
    <title>@yield('title', 'Panel') — Pixel Store</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    {{-- Meta / Open Graph --}}
    <meta name="description" content="Pixel Store — Todo el mundo tecnológico, pixel a pixel.">
    <meta property="og:image" content="{{ asset('images/logo/pixel-logo-horizontal.png') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|chakra-petch:400,500,600,700" rel="stylesheet" />

    {{-- Scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-surface text-on-surface font-body-md" x-data="carritoPublico()">
    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-obsidian-lg focus:bg-primary-container focus:px-4 focus:py-2 focus:font-label-md focus:text-label-md focus:uppercase focus:tracking-[0.14em] focus:text-on-primary-container">
        Ir al contenido
    </a>
    <div class="min-h-screen flex">

        {{-- SIDEBAR --}}
        <aside class="w-64 bg-surface-container-lowest/80 border-r border-outline-variant/30 backdrop-blur-xl flex flex-col">
            {{-- Logo --}}
            <div class="h-16 flex items-center px-4 border-b border-outline-variant/30">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                    <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                         alt="Pixel Store"
                         class="w-9 h-9 rounded group-hover:scale-105 transition-transform">
                    <div class="flex flex-col leading-none">
                        <span class="font-headline-md text-headline-sm tracking-tight text-on-surface">PIXEL STORE</span>
                        <span class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">TECNOLOGÍA SERIA</span>
                    </div>
                </a>
            </div>

            {{-- Navegación --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5">
                @php
                    $usuario = auth()->user();

                    $puede = static fn (?string $permiso): bool => $permiso === null
                        ? true
                        : (bool) $usuario?->can($permiso);

                    $grupos = [
                        'Operación' => [
                            ['texto' => 'Dashboard', 'ruta' => 'dashboard', 'activo' => 'dashboard', 'icono' => 'home'],
                            ['texto' => 'Punto de Venta', 'ruta' => 'admin.ventas.create', 'activo' => 'admin.ventas.create', 'icono' => 'cash', 'permiso' => 'crear ventas'],
                            ['texto' => 'Ventas', 'ruta' => 'admin.ventas.index', 'activo' => 'admin.ventas.index', 'icono' => 'clipboard', 'permiso' => 'ver ventas'],
                            ['texto' => 'Pedidos web', 'ruta' => 'admin.pedidos-web.index', 'activo' => 'admin.pedidos-web.*', 'icono' => 'clipboard', 'permiso' => 'ver ventas'],
                            ['texto' => 'Cotizaciones', 'ruta' => 'admin.cotizaciones.index', 'activo' => 'admin.cotizaciones.*', 'icono' => 'clipboard', 'permiso' => 'ver cotizaciones'],
                            ['texto' => 'Devoluciones', 'ruta' => 'admin.devoluciones.index', 'activo' => 'admin.devoluciones.*', 'icono' => 'clipboard', 'permiso' => 'ver devoluciones'],
                            ['texto' => 'Caja', 'ruta' => 'admin.caja.index', 'activo' => 'admin.caja.*', 'icono' => 'cash', 'rol' => ['Admin', 'Cajero']],
                        ],

                        'Catálogo e inventario' => [
                            ['texto' => 'Productos', 'ruta' => 'admin.productos.index', 'activo' => 'admin.productos.*', 'icono' => 'cube', 'permiso' => 'ver productos'],
                            ['texto' => 'Alertas', 'ruta' => 'alertas.index', 'activo' => 'alertas.*', 'icono' => 'bell', 'permiso' => 'ver productos'],
                            ['texto' => 'Inventario', 'ruta' => 'admin.inventario.index', 'activo' => 'admin.inventario.*', 'icono' => 'archive', 'permiso' => 'ver inventario'],
                            ['texto' => 'Ubicaciones', 'ruta' => 'admin.ubicaciones.index', 'activo' => 'admin.ubicaciones.*', 'icono' => 'location', 'permiso' => 'ver ubicaciones'],
                            ['texto' => 'Números de Serie', 'ruta' => 'admin.series.index', 'activo' => 'admin.series.*', 'icono' => 'bookmark', 'permiso' => 'ver numeros-serie'],
                            ['texto' => 'Catálogo', 'ruta' => 'catalogo.index', 'activo' => 'catalogo.*', 'icono' => 'cube'],
                            ['texto' => 'Carrito', 'ruta' => 'catalogo.index', 'tipo' => 'carrito'],
                        ],

                        'Comercial' => [
                            ['texto' => 'Clientes', 'ruta' => 'admin.clientes.index', 'activo' => 'admin.clientes.*', 'icono' => 'users', 'permiso' => 'ver clientes'],
                            ['texto' => 'Proveedores', 'ruta' => 'admin.proveedores.index', 'activo' => 'admin.proveedores.*', 'icono' => 'tag', 'permiso' => 'ver proveedores'],
                            ['texto' => 'Compras', 'ruta' => 'admin.compras.index', 'activo' => 'admin.compras.*', 'icono' => 'cube', 'permiso' => 'ver ordenes-compra'],
                        ],

                        'Finanzas' => [
                            ['texto' => 'Facturación', 'ruta' => 'admin.facturacion.index', 'activo' => 'admin.facturacion.*', 'icono' => 'cash', 'permiso' => 'ver facturacion'],
                            ['texto' => 'Reportes', 'ruta' => 'admin.reportes.periodo', 'activo' => 'admin.reportes.*', 'icono' => 'clipboard', 'permiso' => 'ver reportes'],
                        ],

                        'Administración' => [
                            ['texto' => 'Usuarios', 'ruta' => 'admin.usuarios.index', 'activo' => 'admin.usuarios.*', 'icono' => 'users', 'permiso' => 'ver usuarios'],
                            ['texto' => 'Auditoría', 'ruta' => 'admin.auditoria.index', 'activo' => 'admin.auditoria.*', 'icono' => 'archive', 'permiso' => 'ver auditoria'],
                        ],
                    ];

                    $visibles = static function (array $items) use ($puede, $usuario): array {
                        return array_values(array_filter($items, static function (array $item) use ($puede, $usuario): bool {
                            return Route::has($item['ruta'])
                                && $puede($item['permiso'] ?? null)
                                && (empty($item['rol']) || (bool) $usuario?->hasAnyRole($item['rol']));
                        }));
                    };
                @endphp

                @foreach ($grupos as $titulo => $items)
                    @php($delGrupo = $visibles($items))

                    @if ($delGrupo !== [])
                        <p class="px-3 pb-1.5 pt-4 font-label-sm text-label-sm font-semibold uppercase tracking-[0.18em] text-outline">
                            {{ $titulo }}
                        </p>

                        @foreach ($delGrupo as $item)
                            @if (($item['tipo'] ?? null) === 'carrito')
                                {{-- Carrito público: abre el drawer del layout. --}}
                                <button type="button" x-on:click="abierto = true"
                                        x-bind:aria-label="`Carrito: ${totalItems} artículos`"
                                        class="flex w-full items-center gap-3 rounded-obsidian-lg px-3 py-2 font-body-md text-body-md text-on-surface-variant transition-colors hover:bg-surface-container-low/60 hover:text-on-surface">
                                    <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span>Carrito</span>
                                    <span x-show="totalItems > 0" x-cloak x-text="totalItems"
                                          class="ml-auto min-w-[1.25rem] rounded-full bg-primary-container px-1.5 py-0.5 text-center font-label-sm text-label-sm tabular-nums text-on-primary-container"></span>
                                </button>
                            @else
                                <x-nav-link :href="route($item['ruta'])"
                                            :active="request()->routeIs($item['activo'])"
                                            :icon="$item['icono']">
                                    {{ $item['texto'] }}
                                </x-nav-link>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </nav>

            {{-- User info --}}
            <div class="border-t border-outline-variant/30 p-4">
                @auth
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center text-on-primary-container font-semibold text-body-sm">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-body-sm font-medium text-on-surface truncate">{{ auth()->user()->name }}</p>
                            <p class="font-label-sm text-label-sm text-outline truncate">
                                {{ auth()->user()->getRoleNames()->first() ?? 'Sin rol' }}
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" data-logout-form class="mt-3">
                        @csrf
                        <button type="submit" class="w-full text-left font-label-sm text-label-sm text-on-surface-variant hover:text-on-surface transition">
                            Cerrar sesión
                        </button>
                    </form>
                @else
                    {{-- El catálogo público usa este layout hasta que exista layouts.public. --}}
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-on-surface-variant transition hover:text-on-surface">
                        Iniciar sesión
                    </a>
                @endauth
            </div>
        </aside>

        {{-- MAIN --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- HEADER --}}
            <header class="h-16 bg-surface-container-lowest/60 border-b border-outline-variant/30 backdrop-blur-xl flex items-center justify-between px-6">
                <div>
                    <h1 class="font-headline-md text-headline-md text-on-surface">
                        @yield('title', 'Dashboard')
                    </h1>
                    @hasSection('subtitle')
                        <p class="text-body-sm text-on-surface-variant">@yield('subtitle')</p>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    {{-- Contador de favoritos del catálogo (deuda D3). --}}
                    @if (Route::has('catalogo.favoritos.index') && request()->routeIs('catalogo.*'))
                        <a href="{{ route('catalogo.favoritos.index') }}"
                           data-favoritos-contador="{{ $favoritosCount ?? 0 }}"
                           title="Favoritos: {{ $favoritosCount ?? 0 }}"
                           class="flex items-center gap-1.5 text-on-surface-variant transition hover:text-on-surface">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span class="min-w-[1.25rem] text-center font-label-sm text-label-sm tabular-nums">{{ $favoritosCount ?? 0 }}</span>
                        </a>
                    @endif

                    <a href="{{ route('alertas.index') }}" class="relative text-on-surface-variant hover:text-on-surface transition"
                       title="{{ $alertasNoLeidasCount ?? 0 }} alertas sin leer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if (($alertasNoLeidasCount ?? 0) > 0)
                            <span class="absolute -top-1.5 -right-1.5 min-w-[1.1rem] h-[1.1rem] px-1 flex items-center justify-center text-[10px] font-bold text-white bg-red-500 rounded-full">
                                {{ $alertasNoLeidasCount > 99 ? '99+' : $alertasNoLeidasCount }}
                            </span>
                        @endif
                    </a>

                    <span class="font-label-sm text-label-sm text-outline">
                        v1.0 · {{ now()->format('d/m/Y') }}
                    </span>
                </div>
            </header>

            {{-- FLASH MESSAGES --}}
            @if (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif
            @if (session('error'))
                <x-alert type="error" :message="session('error')" />
            @endif
            @if (session('warning'))
                <x-alert type="warning" :message="session('warning')" />
            @endif

            {{-- CONTENT --}}
            <main id="contenido" tabindex="-1" class="flex-1 overflow-y-auto bg-surface p-6 focus:outline-none">
                {{ $slot ?? '' }}
                @yield('content')
            </main>

            {{-- FOOTER --}}
            <footer class="h-12 bg-surface-container-lowest/60 border-t border-outline-variant/30 flex items-center justify-between px-6 font-label-sm text-label-sm text-outline">
                <span>&copy; {{ date('Y') }} Pixel Store — Todo el mundo tecnológico, pixel a pixel</span>
                <span>Laravel {{ app()->version() }} · PostgreSQL</span>
            </footer>
        </div>
    </div>

    {{-- CARRITO PÚBLICO (Alpine + localStorage) --}}
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

                <a href="{{ url('/catalogo/checkout') }}"
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

    {{-- SESSION TIMEOUT --}}
    @auth
        <x-session-timeout :lifetime="config('session.lifetime', 30)" />
    @endauth
</body>
</html>
