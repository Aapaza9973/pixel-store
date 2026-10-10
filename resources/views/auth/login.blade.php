{{--
    Login — sistema "Obsidian Cyber Grid" (Etapa 1).

    Ver docs/design/obsidian-cyber-grid.md.

    - Layout autocontenido (NO extiende layouts/guest): ese layout lo comparten las
      otras 5 vistas de auth y todas usan un panel único centrado. Mismo patrón que
      errors/403.blade.php.
    - Jerarquía: <h1> = "Iniciar sesión" (siempre visible, en el form). El hero de
      marca es un <p>: el panel que lo contiene se oculta en mobile, así nunca queda
      un <h2> huérfano sin <h1>.
    - Instanciado desde la propuesta visual del cliente, NO copiado: sin WebGL (CSS),
      sin Tailwind CDN (@vite), sin Material Symbols (SVG inline), sin telemetría
      inventada y sin credenciales hardcodeadas. El form es real.
    - Galería de componentes DENTRO del aside de marca (no es una 3ª columna).
      Necesita ancho Y alto: se muestra desde 1280x800. A 1280 de ancho el aside
      necesita ~800px de alto (medido); con menos, el contenido desborda y la
      firma se pega al borde inferior. En `lg` (1024-1279) no aparece, así que el
      layout ya aprobado queda intacto. Verificado con Chrome headless midiendo la
      posición del dot emerald de la firma.
    - Todas las animaciones decorativas respetan `prefers-reduced-motion: reduce`
      (excepción: el spinner del CTA, que es feedback funcional).
--}}

@php
    // Datos demo de la galería del panel de marca. Hardcodeados a propósito:
    // moverlos a BD requiere tocar el controlador (fuera de scope de esta tarea).
    $componentes = [
        ['cat' => 'Laptop',  'nombre' => 'ASUS ROG Strix G16',  'spec' => 'RTX 4060 · i7-13650HX', 'icono' => 'laptop'],
        ['cat' => 'GPU',     'nombre' => 'ASUS TUF RTX 4070',   'spec' => '12 GB GDDR6X',          'icono' => 'gpu'],
        ['cat' => 'CPU',     'nombre' => 'AMD Ryzen 7 7800X3D', 'spec' => '8C/16T · AM5',          'icono' => 'cpu'],
        ['cat' => 'SSD',     'nombre' => 'Samsung 990 PRO',     'spec' => '2 TB · NVMe PCIe 4.0',  'icono' => 'ssd'],
        ['cat' => 'Monitor', 'nombre' => 'LG UltraGear 27"',    'spec' => '4K · 144 Hz',           'icono' => 'monitor'],
        ['cat' => 'RAM',     'nombre' => 'Corsair Vengeance',   'spec' => '32 GB DDR5-6000',       'icono' => 'ram'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('Iniciar sesión') }} · Pixel Store</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|geist:400,500,600|jetbrains-mono:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ============ Fondo del panel de marca: grid + glows (CSS puro, sin shader de GPU) ============ */
        .obsidian-grid {
            background-image:
                linear-gradient(to right, rgba(67, 70, 85, .28) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(67, 70, 85, .28) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        .obsidian-glow-primary {
            background: radial-gradient(60% 50% at 18% 8%, rgba(37, 99, 235, .22), transparent 70%);
        }

        .obsidian-glow-tertiary {
            background: radial-gradient(55% 45% at 88% 90%, rgba(0, 219, 233, .12), transparent 70%);
        }

        /* ============ Card glass del formulario ============ */
        .glass-login-panel {
            background:
                radial-gradient(120% 90% at 50% 0%, rgba(37, 99, 235, .16), transparent 60%),
                radial-gradient(80% 60% at 100% 100%, rgba(0, 219, 233, .07), transparent 60%),
                rgba(8, 14, 29, .72);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        /* ============ Entrada escalonada (form + galería) ============
           `backwards` (no `both`): durante el delay el elemento está en el estado
           inicial y, al terminar, la animación se suelta y el elemento vuelve a sus
           estilos normales. Con `both` el keyframe final (`transform: none`) pisaría
           para siempre los transforms de hover/active de los tiles y del CTA. */
        @keyframes rise-in {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: none; }
        }

        .rise-in {
            animation: rise-in .5s cubic-bezier(.2, .7, .2, 1) backwards;
        }

        /* ============ Float continuo de los tiles (1-2px, desincronizado) ============ */
        @keyframes tile-float {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-2px); }
        }

        .tile-float {
            animation: tile-float 5s ease-in-out infinite;
        }

        /* ============ Spotlight del card (sigue al cursor; el JS no corre con
           reduced-motion, así que el layer queda en opacity-0) ============ */
        .card-spotlight {
            background: radial-gradient(400px circle at var(--spot-x, 50%) var(--spot-y, 50%),
                                        rgba(37, 99, 235, .18), transparent 80%);
        }

        /* ============ Galería del panel de marca ============
           Necesita ancho Y alto a la vez. Medido con Chrome headless (posición del
           dot emerald exacto de la firma): a 1280 de ancho el aside necesita ~800px
           de alto. Con 620/700/740/760 el contenido desborda y la firma queda
           pegada al borde inferior (overlay clipped). A 1280x800 entra justo y a
           1280x900/1920x1080 sobra. En lg (1024-1279) queda oculta: el layout ya
           aprobado no cambia. */
        .gallery-brand {
            display: none;
        }

        @media (min-width: 1280px) and (min-height: 800px) {
            .gallery-brand {
                display: block;
            }
        }

        /* ============ Signature: onda de la barra pixel ============ */
        @keyframes pixel-wave {
            0%, 100% { transform: scale(1); opacity: .55; }
            50%      { transform: scale(1.35); opacity: 1; }
        }

        .pixel-cell {
            animation: pixel-wave 1.8s ease-in-out infinite;
        }

        .pixel-cell-live {
            box-shadow: 0 0 10px rgba(37, 99, 235, .55);
        }

        /* Accesibilidad: sin animaciones decorativas si el usuario lo pidió.
           (El spinner del botón se mantiene: es feedback funcional, no decoración.) */
        @media (prefers-reduced-motion: reduce) {
            .pixel-cell,
            .rise-in,
            .tile-float,
            .animate-ping {
                animation: none;
            }
        }
    </style>
</head>
<body class="bg-background font-body-md text-on-surface antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-2">

        {{-- ===================== Panel de marca (solo desktop) ===================== --}}
        <aside class="relative hidden overflow-hidden bg-surface p-10 lg:flex lg:flex-col lg:justify-between xl:p-14">
            {{-- Capas de fondo: grid + velo + glows ambientales --}}
            <div class="obsidian-grid pointer-events-none absolute inset-0" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-surface/40 via-surface/80 to-surface" aria-hidden="true"></div>
            <div class="obsidian-glow-primary pointer-events-none absolute inset-0" aria-hidden="true"></div>
            <div class="obsidian-glow-tertiary pointer-events-none absolute inset-0" aria-hidden="true"></div>

            {{-- Bloque superior: marca + tesis + galería --}}
            <div class="relative flex flex-col gap-8">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                         alt="Pixel Store" class="h-12 w-12">
                    <div>
                        <p class="font-headline-md text-headline-md text-on-surface">PIXEL STORE</p>
                        <p class="text-label-sm uppercase tracking-[0.18em] text-outline">Enterprise OS</p>
                    </div>
                </div>

                <div>
                    {{-- Tesis de marca (el <h1> vive en el formulario: ver cabecera) --}}
                    <p class="font-headline-xl text-headline-xl text-on-surface">
                        Todo el mundo<br>
                        tecnológico,<br>
                        <span class="text-tertiary">pixel a pixel.</span>
                    </p>

                    {{-- Signature: barra de señal pixel, 12 celdas con onda escalonada --}}
                    <div class="mt-6 flex gap-1.5" aria-hidden="true">
                        @for ($i = 0; $i < 12; $i++)
                            @if ($i < 9)
                                <span class="pixel-cell pixel-cell-live h-3 w-3 rounded-obsidian bg-primary-container"
                                      style="animation-delay: {{ number_format($i * 0.18, 2) }}s"></span>
                            @else
                                <span class="h-3 w-3 rounded-obsidian bg-surface-variant"></span>
                            @endif
                        @endfor
                    </div>
                </div>

                {{-- Galería: DENTRO del aside (no es una 3ª columna). --}}
                <div class="gallery-brand">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="h-px w-6 bg-outline-variant/50"></span>
                        <p class="text-label-sm uppercase tracking-[0.18em] text-outline">
                            Lo último en tienda
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 xl:grid-cols-3">
                        @foreach ($componentes as $i => $c)
                            <article class="rise-in group relative flex flex-col rounded-obsidian-lg border border-outline-variant/25 bg-surface-container-low/40 p-3.5 transition-all duration-300 hover:-translate-y-0.5 hover:border-primary-container/40 hover:bg-surface-container-low/70"
                                     style="animation-delay: {{ 100 + $i * 70 }}ms">
                                <div class="tile-float flex flex-1 flex-col gap-2.5"
                                     style="animation-delay: {{ number_format($i * 0.35, 2) }}s">
                                    {{-- Icono del tipo de componente --}}
                                    <div class="flex h-8 w-8 items-center justify-center rounded-obsidian border border-outline-variant/30 bg-surface-container-lowest/60 text-tertiary transition-colors group-hover:text-primary">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            @switch($c['icono'])
                                                @case('laptop')
                                                    <rect x="3" y="5.5" width="18" height="11" rx="2" />
                                                    <path stroke-linecap="round" d="M2 19.5h20" />
                                                    @break

                                                @case('gpu')
                                                    <rect x="2.5" y="6.5" width="19" height="10" rx="1.5" />
                                                    <circle cx="8" cy="11.5" r="2" />
                                                    <circle cx="16" cy="11.5" r="2" />
                                                    @break

                                                @case('cpu')
                                                    <rect x="6.5" y="6.5" width="11" height="11" rx="1.5" />
                                                    <rect x="10" y="10" width="4" height="4" />
                                                    <path stroke-linecap="round"
                                                          d="M10 3.5v3M14 3.5v3M10 17.5v3M14 17.5v3M3.5 10h3M3.5 14h3M17.5 10h3M17.5 14h3" />
                                                    @break

                                                @case('ssd')
                                                    <rect x="3" y="7" width="18" height="10" rx="1.5" />
                                                    <path stroke-linecap="round" d="M7 10.5h7M7 13.5h4" />
                                                    @break

                                                @case('monitor')
                                                    <rect x="2.5" y="4.5" width="19" height="12.5" rx="1.5" />
                                                    <path stroke-linecap="round" d="M12 17v3M8.5 20h7" />
                                                    @break

                                                @case('ram')
                                                    <rect x="2.5" y="8" width="19" height="7" rx="1" />
                                                    <path stroke-linecap="round" d="M6.5 11h4M13.5 11h4" />
                                                    <path stroke-linecap="round" d="M7 15v2.5M11 15v2.5M15 15v2.5M19 15v2.5" />
                                                    @break
                                            @endswitch
                                        </svg>
                                    </div>

                                    <p class="text-label-sm uppercase tracking-[0.18em] text-outline">{{ $c['cat'] }}</p>
                                    <h3 class="font-headline-sm text-sm font-semibold leading-tight text-on-surface">
                                        {{ $c['nombre'] }}
                                    </h3>
                                    <p class="mt-auto truncate text-label-sm text-on-surface-variant/70">{{ $c['spec'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Firma --}}
            <div class="relative border-t border-outline-variant/15 pt-6">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    <p class="text-label-sm uppercase tracking-widest text-outline">
                        Sistema de ventas e inventario · La Paz, Bolivia
                    </p>
                </div>
            </div>
        </aside>

        {{-- ===================== Panel del formulario ===================== --}}
        <main class="relative flex min-h-screen flex-col items-center justify-center bg-surface-container-lowest px-6 py-12 sm:px-12 lg:min-h-0">
            <div class="w-full max-w-md">

                {{-- Logo compacto: solo cuando el panel de marca se oculta --}}
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                         alt="Pixel Store" class="h-10 w-10">
                    <div>
                        <p class="font-headline-sm text-headline-sm text-on-surface">PIXEL STORE</p>
                        <p class="text-label-sm uppercase tracking-[0.18em] text-outline">Enterprise OS</p>
                    </div>
                </div>

                {{-- Card glass --}}
                <div id="login-card"
                     class="glass-login-panel relative rounded-obsidian-xl border border-outline-variant/30 p-7 sm:p-9">
                    {{-- Spotlight que sigue al cursor (A.1). Decorativo: el JS no corre
                         con prefers-reduced-motion, así que queda en opacity-0. --}}
                    <div id="card-spotlight"
                         class="card-spotlight pointer-events-none absolute -inset-px rounded-obsidian-xl opacity-0 transition-opacity duration-300"
                         aria-hidden="true"></div>

                    {{-- Badge eyebrow --}}
                    <div class="rise-in inline-flex items-center gap-2 rounded-obsidian border border-outline-variant/40 bg-surface-container-low/60 px-2.5 py-1"
                         style="animation-delay: 0ms">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-tertiary opacity-75"></span>
                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-tertiary"></span>
                        </span>
                        <span class="text-label-sm uppercase tracking-[0.18em] text-on-surface-variant">
                            Acceso al sistema
                        </span>
                    </div>

                    <h1 class="rise-in mt-6 font-headline-lg text-headline-lg-mobile text-on-surface sm:text-headline-lg"
                        style="animation-delay: 80ms">
                        {{ __('Iniciar sesión') }}
                    </h1>
                    <p class="rise-in mt-2 text-body-md text-on-surface-variant" style="animation-delay: 140ms">
                        Ingresá tus credenciales para acceder al panel.
                    </p>

                    {{-- Estado de la sesión --}}
                    @if (session('status'))
                        <p class="mt-4 text-body-sm text-tertiary">{{ session('status') }}</p>
                    @endif

                    @if ($errors->any())
                        <div class="mt-5 flex items-start gap-2.5 rounded-obsidian-lg border border-error/25 bg-error-container/25 px-3.5 py-3">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-error" fill="none" viewBox="0 0 24 24"
                                 stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                            <div class="text-body-sm text-error">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5"
                          x-data="loginForm({ email: {{ json_encode(old('email')) }}, hasServerEmailError: {{ $errors->has('email') ? 'true' : 'false' }}, hasServerPasswordError: {{ $errors->has('password') ? 'true' : 'false' }} })"
                          x-on:submit="enviando = true">
                        @csrf

                        {{-- Correo electrónico --}}
                        <div class="rise-in" style="animation-delay: 200ms">
                            <label for="email"
                                   class="mb-2 block text-label-md uppercase tracking-[0.12em] text-outline">
                                {{ __('Correo electrónico') }}
                            </label>
                            <div class="group relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-outline transition-colors group-focus-within:text-primary"
                                      aria-hidden="true">
                                    {{-- mail --}}
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                </span>
                                <input id="email" name="email" type="email" required autofocus
                                       autocomplete="username"
                                       value="{{ old('email') }}"
                                       x-on:blur="emailTouched = true"
                                       :aria-invalid="emailAriaInvalid ? 'true' : 'false'"
                                       :aria-describedby="emailAriaInvalid ? (hasServerEmailError ? 'email-error-server' : 'email-error-client') : null"
                                       placeholder="tu@pixelstore.com"
                                       x-model="email"
                                       class="block w-full rounded-obsidian-lg border bg-surface-container-low py-3 pl-11 pr-4 text-body-md text-on-surface placeholder:text-outline/70 transition focus:outline-none focus:ring-[3px]"
                                       :class="emailInvalid
                                               ? 'border-error/60 focus:border-error focus:ring-error/20'
                                               : 'border-outline-variant/30 focus:border-primary-container focus:ring-primary-container/25'" />
                            </div>

                            {{-- Error de cliente (Alpine). Se oculta si hay error de servidor. --}}
                            <p id="email-error-client" x-cloak x-show="emailError !== ''"
                               x-transition.opacity.duration.150ms
                               class="mt-1.5 flex items-center gap-1.5 text-body-sm text-error">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                </svg>
                                <span x-text="emailError"></span>
                            </p>

                            {{-- Error de servidor (tiene prioridad sobre el de cliente) --}}
                            @error('email')
                                <p id="email-error-server" class="mt-1.5 flex items-center gap-1.5 text-body-sm text-error">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Contraseña --}}
                        <div class="rise-in" style="animation-delay: 260ms">
                            <label for="password"
                                   class="mb-2 block text-label-md uppercase tracking-[0.12em] text-outline">
                                {{ __('Contraseña') }}
                            </label>
                            <div class="group relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-outline transition-colors group-focus-within:text-primary"
                                      aria-hidden="true">
                                    {{-- lock --}}
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </span>
                                <input id="password" name="password"
                                       :type="showPassword ? 'text' : 'password'"
                                       type="password"
                                       required autocomplete="current-password"
                                       x-model="password"
                                       x-on:blur="passwordTouched = true"
                                       :aria-invalid="passwordAriaInvalid ? 'true' : 'false'"
                                       :aria-describedby="passwordAriaInvalid ? (hasServerPasswordError ? 'password-error-server' : 'password-error-client') : null"
                                       class="block w-full rounded-obsidian-lg border bg-surface-container-low py-3 pl-11 pr-12 text-body-md text-on-surface placeholder:text-outline/70 transition focus:outline-none focus:ring-[3px]"
                                       :class="passwordInvalid
                                               ? 'border-error/60 focus:border-error focus:ring-error/20'
                                               : 'border-outline-variant/30 focus:border-primary-container focus:ring-primary-container/25'" />

                                {{-- Toggle de visibilidad (Alpine ya viene en app.js) --}}
                                <button type="button" x-on:click="showPassword = !showPassword"
                                        aria-controls="password"
                                        :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-outline transition hover:text-on-surface-variant focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/40 rounded-obsidian">
                                    <svg x-show="!showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
                            {{-- Error de cliente (Alpine). Se oculta si hay error de servidor. --}}
                            <p id="password-error-client" x-cloak x-show="passwordError !== ''"
                               x-transition.opacity.duration.150ms
                               class="mt-1.5 flex items-center gap-1.5 text-body-sm text-error">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                </svg>
                                <span x-text="passwordError"></span>
                            </p>

                            {{-- Error de servidor (tiene prioridad sobre el de cliente) --}}
                            @error('password')
                                <p id="password-error-server" class="mt-1.5 flex items-center gap-1.5 text-body-sm text-error">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Recordarme / recuperar contraseña --}}
                        <div class="rise-in flex items-center justify-between gap-4 pt-1" style="animation-delay: 320ms">
                            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2">
                                <input id="remember_me" type="checkbox" name="remember"
                                       class="rounded-obsidian border-outline-variant bg-surface-container-low text-primary-container transition focus:ring-2 focus:ring-primary-container/40 focus:ring-offset-0" />
                                <span class="text-body-sm text-on-surface-variant">{{ __('Recordarme') }}</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}"
                                   class="rounded-obsidian text-body-sm text-secondary transition hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/40">
                                    {{ __('¿Olvidaste tu contraseña?') }}
                                </a>
                            @endif
                        </div>

                        {{-- CTA con loading real (el submit sigue siendo de Laravel) --}}
                        <button type="submit"
                                x-bind:disabled="enviando"
                                style="animation-delay: 380ms"
                                class="rise-in inline-flex w-full items-center justify-center gap-2 rounded-obsidian-lg bg-primary-container px-5 py-3 text-body-md font-medium text-on-primary-container shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_0_24px_rgba(37,99,235,0.35)] transition-all duration-200 hover:bg-primary-container/90 hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.25),0_0_32px_rgba(37,99,235,0.55)] active:scale-[0.985] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60 focus-visible:ring-offset-2 focus-visible:ring-offset-surface-container-lowest disabled:cursor-not-allowed disabled:opacity-70">
                            <svg x-cloak x-show="enviando" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" />
                            </svg>
                            <span x-show="!enviando">{{ __('Iniciar sesión') }}</span>
                            <span x-cloak x-show="enviando">Verificando…</span>
                        </button>
                    </form>
                </div>

                {{-- Footer --}}
                <p class="mt-10 text-center text-label-md text-outline/70">
                    &copy; {{ date('Y') }} Pixel Store
                </p>
            </div>
        </main>
    </div>

    {{-- Validación en tiempo real del login (Alpine). No bloquea el submit: Laravel
         sigue validando en el backend. Los errores de servidor tienen prioridad. --}}
    <script>
        function loginForm(initial) {
            return {
                email: initial.email || '',
                password: '',
                showPassword: false,
                enviando: false,
                emailTouched: false,
                passwordTouched: false,
                hasServerEmailError: initial.hasServerEmailError,
                hasServerPasswordError: initial.hasServerPasswordError,

                get emailError() {
                    if (this.hasServerEmailError) return '';
                    if (! this.emailTouched) return '';
                    if (this.email === '') return 'Ingresá tu correo electrónico.';
                    if (! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) return 'Ingresá un correo válido.';
                    return '';
                },

                get passwordError() {
                    if (this.hasServerPasswordError) return '';
                    if (! this.passwordTouched) return '';
                    if (this.password === '') return 'Ingresá tu contraseña.';
                    return '';
                },

                get emailInvalid() { return this.emailError !== ''; },
                get passwordInvalid() { return this.passwordError !== ''; },
                get emailAriaInvalid() { return this.hasServerEmailError || this.emailInvalid; },
                get passwordAriaInvalid() { return this.hasServerPasswordError || this.passwordInvalid; },
            };
        }
    </script>

    {{-- Spotlight del card que sigue al cursor (A.1). Vanilla, ~15 líneas.
         Si el usuario pidió menos movimiento, no se engancha ningún listener y el
         layer queda en opacity-0. --}}
    <script>
        (function () {
            var card = document.getElementById('login-card');
            var spot = document.getElementById('card-spotlight');

            if (!card || !spot) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            card.addEventListener('mousemove', function (e) {
                var rect = card.getBoundingClientRect();
                spot.style.setProperty('--spot-x', ((e.clientX - rect.left) / rect.width * 100) + '%');
                spot.style.setProperty('--spot-y', ((e.clientY - rect.top) / rect.height * 100) + '%');
                spot.style.opacity = '1';
            });

            card.addEventListener('mouseleave', function () {
                spot.style.opacity = '0';
            });
        })();
    </script>
</body>
</html>
