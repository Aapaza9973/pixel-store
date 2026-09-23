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
<body class="font-sans antialiased bg-slate-950 text-slate-200">
    <div class="min-h-screen flex">

        {{-- SIDEBAR --}}
        <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col">
            {{-- Logo --}}
            <div class="h-16 flex items-center px-4 border-b border-slate-800">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                    <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                         alt="Pixel Store"
                         class="w-9 h-9 rounded group-hover:scale-105 transition-transform">
                    <div class="flex flex-col leading-none">
                        <span class="text-white font-bold text-sm tracking-tight">PIXEL STORE</span>
                        <span class="text-[10px] text-slate-500 tracking-wider">TECNOLOGÍA SERIA</span>
                    </div>
                </a>
            </div>

            {{-- Navegación --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">
                    Dashboard
                </x-nav-link>

                @can('ver productos')
                    <x-nav-link :href="'#'" :active="request()->routeIs('productos.*')" icon="cube">
                        Productos
                    </x-nav-link>
                @endcan

                @can('ver categorias')
                    <x-nav-link :href="route('admin.categorias.index')" :active="request()->routeIs('admin.categorias.*')" icon="tag">
                        Categorías
                    </x-nav-link>
                @endcan

                @can('ver marcas')
                    <x-nav-link :href="'#'" :active="request()->routeIs('marcas.*')" icon="bookmark">
                        Marcas
                    </x-nav-link>
                @endcan

                @can('ver atributos')
                    <x-nav-link :href="'#'" :active="request()->routeIs('atributos.*')" icon="adjustments">
                        Atributos técnicos
                    </x-nav-link>
                @endcan

                @can('ver ubicaciones')
                    <x-nav-link :href="'#'" :active="request()->routeIs('ubicaciones.*')" icon="location">
                        Ubicaciones
                    </x-nav-link>
                @endcan

                @can('ver usuarios')
                    <div class="pt-4 pb-2 px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Administración
                    </div>
                    <x-nav-link :href="'#'" :active="request()->routeIs('usuarios.*')" icon="users">
                        Usuarios
                    </x-nav-link>
                    <x-nav-link :href="'#'" :active="request()->routeIs('auditoria.*')" icon="clipboard">
                        Auditoría
                    </x-nav-link>
                @endcan
            </nav>

            {{-- User info --}}
            <div class="border-t border-slate-800 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 truncate">
                            {{ auth()->user()->getRoleNames()->first() ?? 'Sin rol' }}
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full text-left text-xs text-slate-400 hover:text-white transition">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        {{-- MAIN --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- HEADER --}}
            <header class="h-16 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-6">
                <div>
                    <h1 class="text-lg font-semibold text-white">
                        @yield('title', 'Dashboard')
                    </h1>
                    @hasSection('subtitle')
                        <p class="text-xs text-slate-500">@yield('subtitle')</p>
                    @endif
                </div>

                <div class="flex items-center gap-4">
                    <a href="#" class="relative text-slate-400 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute -top-1 -right-1 w-2 h-2 bg-blue-500 rounded-full"></span>
                    </a>

                    <span class="text-xs text-slate-500">
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
            <main class="flex-1 p-6 overflow-y-auto">
                {{ $slot ?? '' }}
                @yield('content')
            </main>

            {{-- FOOTER --}}
            <footer class="h-12 bg-slate-900 border-t border-slate-800 flex items-center justify-between px-6 text-xs text-slate-500">
                <span>&copy; {{ date('Y') }} Pixel Store — Todo el mundo tecnológico, pixel a pixel</span>
                <span>Laravel {{ app()->version() }} · PostgreSQL</span>
            </footer>
        </div>
    </div>

    {{-- SESSION TIMEOUT --}}
    @auth
        <x-session-timeout :lifetime="config('session.lifetime', 30)" />
    @endauth
</body>
</html>
