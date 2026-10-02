@php
    // Nunca exponer $exception ni su mensaje: esta vista se renderiza en producción
    // ante un error inesperado y no debe filtrar información técnica.
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
    <title>500 — Error del servidor — Pixel Store</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|chakra-petch:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased min-h-screen bg-slate-950 text-slate-200 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <x-card class="text-center">
            <div class="flex justify-center pb-2">
                {{-- Triángulo de alerta (SVG inline, sin dependencias externas) --}}
                <svg class="w-24 h-24 text-red-400" fill="none" stroke="currentColor"
                     stroke-width="1.5" viewBox="0 0 24 24" role="img" aria-label="Error del servidor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wider text-slate-400 uppercase">Error 500</p>
                <h1 class="text-3xl font-bold text-white">Algo salió mal</h1>
                <p class="text-slate-400">Hubo un error inesperado. Ya estamos al tanto.</p>
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
                    Si el problema persiste, contacta al administrador del sistema.
                </p>
            @endauth
        </x-card>
    </div>
</body>
</html>
