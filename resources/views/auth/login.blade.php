{{--
    Login con split de 2 columnas: marca (izquierda) + formulario (derecha).

    Layout autocontenido a propósito: `layouts/guest` lo comparten las otras vistas
    de auth (register, forgot-password, reset-password, verify-email, confirm-password)
    y todas usan un panel único centrado. Tocarlo obligaría a propagar una prop de
    variante a las seis. Es el mismo patrón que `errors/403.blade.php`.
    Costo asumido: el <head> (favicon + fuentes) queda duplicado.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('Iniciar sesión') }} · Pixel Store</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|chakra-petch:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Grid pixelado: signature de fondo del panel de marca (solo desktop). */
        .pixel-grid {
            background-image:
                linear-gradient(to right, rgba(30, 41, 59, .35) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(30, 41, 59, .35) 1px, transparent 1px);
            background-size: 32px 32px;
        }
    </style>
</head>
<body class="font-sans antialiased bg-slate-900 text-slate-200">
    <div class="min-h-screen lg:grid lg:grid-cols-2">

        {{-- ================= Panel izquierdo — marca (solo desktop) ================= --}}
        <aside class="relative hidden overflow-hidden bg-slate-950 p-12 lg:flex lg:flex-col lg:justify-between xl:p-16">
            {{-- Grid pixelado + velo: el fondo no compite con el texto --}}
            <div class="pixel-grid pointer-events-none absolute inset-0" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-slate-950/70 via-slate-950/85 to-slate-950" aria-hidden="true"></div>

            {{-- Bloque de marca --}}
            <div class="relative">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                         alt="Pixel Store" class="h-14 w-14">
                    <span class="font-display text-3xl font-bold text-white tracking-tight">
                        PIXEL STORE
                    </span>
                </div>

                {{-- Tesis de marca. El <h1> del documento es "Iniciar sesión" (ver abajo):
                     este bloque es copy de marca, siempre visible solo en desktop. --}}
                <p class="mt-16 font-display text-6xl font-bold text-white leading-none tracking-tighter">
                    Todo el mundo<br>
                    tecnológico,<br>
                    <span class="text-blue-500">pixel a pixel.</span>
                </p>

                {{-- Barra de señal pixel (signature): 12 celdas --}}
                <div class="mt-10 flex gap-1" aria-hidden="true">
                    @for ($i = 0; $i < 12; $i++)
                        <span class="h-3 w-3 rounded-sm {{ $i < 9 ? 'bg-blue-400' : 'bg-slate-800' }}"></span>
                    @endfor
                </div>
            </div>

            {{-- Bloque de firma --}}
            <div class="relative">
                <div class="h-px w-24 bg-slate-800 mb-6"></div>
                <p class="font-mono text-xs uppercase tracking-widest text-slate-500">
                    Sistema de ventas e inventario · La Paz, Bolivia
                </p>
            </div>
        </aside>

        {{-- ================= Panel derecho — formulario ================= --}}
        <main class="flex min-h-screen flex-col items-center justify-center bg-slate-900 px-6 py-12 sm:px-12 lg:min-h-0">
            <div class="w-full max-w-md">

                {{-- Logo compacto: solo en mobile (el panel de marca se oculta) --}}
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                         alt="Pixel Store" class="h-10 w-10">
                    <span class="font-display text-xl font-bold text-white tracking-tight">
                        PIXEL STORE
                    </span>
                </div>

                {{-- Eyebrow --}}
                <p class="mb-2 font-mono text-xs uppercase tracking-widest text-blue-400">
                    Acceso al sistema
                </p>

                <h1 class="mb-2 font-display text-3xl font-bold text-white">
                    {{ __('Iniciar sesión') }}
                </h1>
                <p class="mb-8 text-sm text-slate-400">
                    Ingresá tus credenciales para acceder al panel.
                </p>

                {{-- Estado de la sesión --}}
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- Correo electrónico --}}
                    <div>
                        <x-input-label for="email" :value="__('Correo electrónico')" />
                        <x-text-input id="email" name="email" type="email"
                                      class="mt-1.5 block w-full"
                                      :value="old('email')" required autofocus
                                      autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    {{-- Contraseña --}}
                    <div>
                        <x-input-label for="password" :value="__('Contraseña')" />
                        <x-text-input id="password" name="password" type="password"
                                      class="mt-1.5 block w-full"
                                      autocomplete="current-password" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    {{-- Recordarme / recuperar contraseña --}}
                    <div class="flex items-center justify-between pt-2">
                        <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                            <input id="remember_me" type="checkbox" name="remember"
                                   class="rounded border-slate-700 bg-slate-800 text-blue-600 shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                            <span class="text-sm text-slate-300">{{ __('Recordarme') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="rounded-lg text-sm text-blue-400 hover:text-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                                {{ __('¿Olvidaste tu contraseña?') }}
                            </a>
                        @endif
                    </div>

                    <x-primary-button class="w-full justify-center">
                        {{ __('Iniciar sesión') }}
                    </x-primary-button>
                </form>

                {{-- Footer discreto --}}
                <p class="mt-12 text-center text-xs text-slate-600">
                    &copy; {{ date('Y') }} Pixel Store
                </p>
            </div>
        </main>
    </div>
</body>
</html>
