@php
    $autenticado = auth()->check();
    $urlVolver = $autenticado
        ? route('dashboard')
        : (Route::has('login') ? route('login') : '/');
    $textoVolver = $autenticado ? 'Volver al dashboard' : 'Ir al inicio';
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Página no encontrada — Pixel Store</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|chakra-petch:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased min-h-screen bg-slate-950 text-slate-200 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <x-card class="text-center">
            <div class="flex justify-center pb-2">
                {{-- Lupa (SVG inline, sin dependencias externas) --}}
                <svg class="w-24 h-24 text-blue-400" fill="none" stroke="currentColor"
                     stroke-width="1.5" viewBox="0 0 24 24" role="img" aria-label="Página no encontrada">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wider text-slate-400 uppercase">Error 404</p>
                <h1 class="text-3xl font-bold text-white">Página no encontrada</h1>
                <p class="text-slate-400">La página que buscás no existe o fue movida.</p>
            </div>

            <div class="pt-4">
                <a href="{{ $urlVolver }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    {{ $textoVolver }}
                </a>
            </div>

            @auth
                <p class="pt-5 text-xs text-slate-500">
                    Sesión activa como <span class="text-slate-400">{{ auth()->user()->email }}</span>.
                    Si crees que esto es un error, contacta al administrador del sistema.
                </p>
            @endauth
        </x-card>
    </div>
</body>
</html>
