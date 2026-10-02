<!DOCTYPE html>
<html lang="es">
{{--
    Spike /preview — Landing pública de Pixel Store.
    Layout autocontenido a propósito: NO extiende layouts.app (spike descartable).
    Validación de skills/frontend-design.md aplicada al CATÁLOGO PÚBLICO.
--}}
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Pixel Store — Hardware con la ficha técnica a la vista</title>

    {{-- Favicon obligatorio del skill --}}
    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    {{-- Tipografía de marca (mismas familias del layout, no se agregan nuevas) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|chakra-petch:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* El tailwind.config no mapea font-display/font-body; se resuelve en la vista
           para no alterar el layout global ni el config (fuera de scope del spike). */
        .font-display { font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif; }
        .font-body    { font-family: 'Chakra Petch', ui-sans-serif, system-ui, sans-serif; }

        /* Elemento signature: la "barra de señal pixel" (ver HTML). */
        .pixel-grid {
            background-image:
                linear-gradient(to right, rgba(30, 41, 59, .55) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(30, 41, 59, .55) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        /* Motion orquestado: solo el hero entra en secuencia; el resto es estático. */
        @keyframes rise {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .rise { animation: rise .6s cubic-bezier(.2, .7, .2, 1) both; }

        @media (prefers-reduced-motion: reduce) {
            .rise { animation: none; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-950 font-body text-slate-300 antialiased">

    {{-- ============ HEADER ============ --}}
    <header class="sticky top-0 z-20 border-b border-slate-800 bg-slate-950/80 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
            {{-- Marca: icono + wordmark (tratamiento establecido en el sidebar del panel). --}}
            <a href="#" class="flex items-center gap-3">
                <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                     alt="Pixel Store"
                     class="h-9 w-9 rounded">
                <span class="flex flex-col leading-none">
                    <span class="font-display text-sm font-bold tracking-tight text-white">PIXEL STORE</span>
                    <span class="font-mono text-xs tracking-widest text-slate-500">TECNOLOGÍA SERIA</span>
                </span>
            </a>

            <nav class="hidden items-center gap-8 text-sm text-slate-400 md:flex">
                <a href="#" class="transition hover:text-white">Componentes</a>
                <a href="#" class="transition hover:text-white">Almacenamiento</a>
                <a href="#" class="transition hover:text-white">Periféricos</a>
            </nav>

            <a href="#" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                Cotizar equipo
            </a>
        </div>
    </header>

    <main>

        {{-- ============ HERO — "la ficha técnica como tesis" ============ --}}
        <section class="relative overflow-hidden border-b border-slate-800">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-blue-600/10 to-transparent"></div>
            <div class="pixel-grid pointer-events-none absolute inset-0 opacity-40"></div>

            <div class="relative mx-auto grid max-w-6xl gap-12 px-6 py-16 lg:grid-cols-2 lg:items-center lg:py-24">

                {{-- Columna izquierda: la tesis en texto --}}
                <div>
                    <p class="rise font-mono text-xs uppercase tracking-widest text-blue-400" style="animation-delay:.05s">
                        // Hardware de escritorio · La Paz, Bolivia
                    </p>

                    <h1 class="rise mt-5 font-display text-5xl font-bold leading-none tracking-tight text-white" style="animation-delay:.12s">
                        Cada componente, con su <span class="text-blue-400">ficha técnica</span> a la vista.
                    </h1>

                    <p class="rise mt-6 max-w-xl text-base leading-relaxed text-slate-400" style="animation-delay:.2s">
                        Componentes, almacenamiento y periféricos para armar tu equipo.
                        Stock real en tienda, precios en bolivianos y las especificaciones completas. Sin letra chica.
                    </p>

                    <div class="rise mt-8 flex flex-wrap items-center gap-4" style="animation-delay:.28s">
                        <a href="#destacados"
                           class="inline-flex items-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-medium text-white transition hover:bg-blue-700">
                            Ver catálogo
                        </a>
                        <a href="#"
                           class="inline-flex items-center rounded-lg border border-slate-700 px-6 py-3 text-sm font-medium text-slate-300 transition hover:border-slate-500 hover:text-white">
                            Cotizar equipo
                        </a>
                    </div>

                    <p class="rise mt-6 font-mono text-xs text-slate-500" style="animation-delay:.36s">
                        148 SKUs en catálogo · 6 ubicaciones · reparto en 24 h
                    </p>
                </div>

                {{-- Columna derecha: la tesis hecha dato — la ficha técnica como héroe --}}
                <div class="rise" style="animation-delay:.3s">
                    <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">

                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs uppercase tracking-widest text-blue-400">// Ficha técnica</span>
                            <span class="font-mono text-xs text-slate-500">GPU-RTX4070-TUF</span>
                        </div>

                        <h2 class="mt-4 font-display text-xl font-semibold tracking-tight text-white">
                            ASUS GeForce RTX 4070 TUF OC
                        </h2>

                        <dl class="mt-5 divide-y divide-slate-800 border-y border-slate-800 font-mono text-sm">
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">GPU</dt>
                                <dd class="text-slate-200">NVIDIA AD104</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">Memoria</dt>
                                <dd class="text-slate-200">12 GB GDDR6X</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">Interfaz</dt>
                                <dd class="text-slate-200">192-bit</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">Reloj boost</dt>
                                <dd class="text-slate-200">2.610 MHz</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-slate-500">Consumo</dt>
                                <dd class="text-slate-200">200 W</dd>
                            </div>
                        </dl>

                        <div class="mt-5 flex items-end justify-between">
                            <div>
                                <p class="font-mono text-xs text-slate-500">Precio</p>
                                <p class="font-display text-2xl font-bold tabular-nums text-white">Bs 4.890</p>
                            </div>
                            <div class="text-right">
                                <p class="font-mono text-xs text-slate-500">Disponibilidad</p>
                                <div class="mt-2 flex items-center justify-end gap-2">
                                    <div class="flex gap-0.5" aria-hidden="true">
                                        @for ($i = 0; $i < 12; $i++)
                                            <span class="h-3 w-1.5 rounded-sm bg-blue-400"></span>
                                        @endfor
                                    </div>
                                    <span class="font-mono text-xs text-emerald-400">12 u.</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        {{-- ============ PRODUCTOS DESTACADOS ============ --}}
        <section id="destacados" class="mx-auto max-w-6xl px-6 py-20">

            <div class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="font-mono text-xs uppercase tracking-widest text-blue-400">// En stock hoy</p>
                    <h2 class="mt-3 font-display text-3xl font-bold tracking-tight text-white">
                        Componentes disponibles
                    </h2>
                </div>
                <p class="font-mono text-xs text-slate-500">06 productos</p>
            </div>

            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($destacados as $producto)
                    @php
                        $stock = $producto['stock'];

                        $estado = match (true) {
                            $stock === 0 => 'agotado',
                            $stock < 6   => 'bajo',
                            default      => 'disponible',
                        };

                        $etiquetas = [
                            'disponible' => 'Disponible',
                            'bajo'       => 'Stock bajo',
                            'agotado'    => 'Agotado',
                        ];

                        $badges = [
                            'disponible' => 'border-emerald-500/20 bg-emerald-500/10 text-emerald-400',
                            'bajo'       => 'border-amber-500/20 bg-amber-500/10 text-amber-400',
                            'agotado'    => 'border-red-500/20 bg-red-500/10 text-red-400',
                        ];

                        $celdas = min($stock, 12);
                        $colorCelda = match ($estado) {
                            'bajo'    => 'bg-amber-400',
                            'agotado' => 'bg-slate-800',
                            default   => 'bg-blue-400',
                        };
                    @endphp

                    <article class="group flex flex-col rounded-xl border border-slate-800 bg-slate-900 p-5 transition hover:border-blue-500/40">

                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs uppercase tracking-wider text-slate-500">{{ $producto['categoria'] }}</span>
                            <span class="rounded-lg border px-2 py-0.5 text-xs font-medium {{ $badges[$estado] }}">
                                {{ $etiquetas[$estado] }}
                            </span>
                        </div>

                        <p class="mt-5 text-xs font-medium uppercase tracking-widest text-blue-400">{{ $producto['marca'] }}</p>
                        <h3 class="mt-1 font-display text-lg font-semibold tracking-tight text-white">{{ $producto['modelo'] }}</h3>
                        <p class="mt-2 font-mono text-xs text-slate-400">{{ $producto['specs'] }}</p>
                        <p class="mt-1 font-mono text-xs text-slate-600">{{ $producto['sku'] }}</p>

                        <div class="mt-auto pt-6">
                            <div class="flex items-end justify-between">
                                <p class="font-display text-lg font-bold tabular-nums text-white">
                                    Bs {{ number_format($producto['precio'], 0, ',', '.') }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <div class="flex gap-0.5" aria-hidden="true">
                                        @for ($i = 0; $i < 12; $i++)
                                            <span class="h-2 w-1 rounded-sm {{ $i < $celdas ? $colorCelda : 'bg-slate-800' }}"></span>
                                        @endfor
                                    </div>
                                    <span class="font-mono text-xs {{ $estado === 'agotado' ? 'text-slate-500' : 'text-slate-400' }}">
                                        {{ $stock }} u.
                                    </span>
                                </div>
                            </div>
                        </div>

                    </article>
                @endforeach
            </div>
        </section>
    </main>

    {{-- ============ FOOTER ============ --}}
    <footer class="border-t border-slate-800">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 px-6 py-10 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                     alt="Pixel Store"
                     class="h-8 w-8 rounded">
                <span class="flex flex-col leading-none">
                    <span class="font-display text-sm font-bold tracking-tight text-white">PIXEL STORE</span>
                    <span class="font-mono text-xs tracking-widest text-slate-500">TODO EL MUNDO TECNOLÓGICO, PIXEL A PIXEL</span>
                </span>
            </div>

            <p class="font-mono text-xs text-slate-500">
                La Paz, Bolivia · © {{ date('Y') }} Pixel Store · Mockup /preview
            </p>
        </div>
    </footer>

</body>
</html>
