@php
    $mensajeCrudo = trim((string) (($exception ?? null)?->getMessage() ?? ''));

    // Las denegaciones de policy llegan con el mensaje por defecto de Laravel
    // (en inglés): en ese caso mostramos el texto genérico en español.
    $mensaje = in_array($mensajeCrudo, ['', 'This action is unauthorized.'], true)
        ? 'No tienes permiso para acceder a esta sección.'
        : $mensajeCrudo;

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
    <title>403 — Acceso denegado — Pixel Store</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|chakra-petch:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased min-h-screen bg-slate-950 text-slate-200 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <x-card class="text-center">
            <div class="flex justify-center pb-2">
                {{-- Escudo (SVG inline, sin dependencias externas) --}}
                <svg class="w-24 h-24 text-amber-500" fill="none" stroke="currentColor"
                     stroke-width="1.5" viewBox="0 0 24 24" role="img" aria-label="Acceso denegado">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 15v-4.5M12 18.75h.007v.008H12v-.008Z" />
                </svg>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wider text-slate-400 uppercase">Error 403</p>
                <h1 class="text-3xl font-bold text-white">Acceso denegado</h1>
                <p class="text-slate-400">{{ $mensaje }}</p>
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
