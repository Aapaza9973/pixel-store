<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante {{ $numero }} — Pixel Store</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    {{-- Mismo bundle del proyecto; Alpine viene en app.js para el botón Imprimir. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- En papel no se imprime el tema oscuro: fondo blanco y tinta negra. --}}
    <style>
        @media print {
            .comprobante { background: #fff !important; border: 0 !important; }
            .comprobante,
            .comprobante .text-on-surface,
            .comprobante .text-on-surface-variant,
            .comprobante .text-outline,
            .comprobante .text-secondary { color: #111 !important; }
            .comprobante .border-outline-variant\/30 { border-color: #d4d4d4 !important; }
            .comprobante .bg-surface-container-low { background: #f5f5f5 !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-surface font-body-md text-on-surface antialiased">
    <div class="mx-auto max-w-3xl px-6 py-8">

        {{-- Controles de pantalla: nunca salen en papel. --}}
        <div class="mb-4 flex items-center justify-between gap-3 print:hidden">
            <a href="{{ route('admin.ventas.create') }}"
               class="inline-flex items-center gap-2 rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2 text-body-md text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
                ← Volver al POS
            </a>

            <button type="button" x-on:click="window.print()"
                    class="inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-4 py-2.5 text-body-md font-medium text-on-primary-container shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_0_20px_rgba(37,99,235,0.35)] transition hover:bg-primary-container/90">
                Imprimir
            </button>
        </div>

        <article class="comprobante overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">

            {{-- Cabecera: marca + número + fecha --}}
            <header class="flex flex-wrap items-start justify-between gap-6 border-b border-outline-variant/30 px-8 py-6">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                         alt="Pixel Store"
                         class="h-10 w-10 rounded">
                    <div class="leading-none">
                        <p class="font-headline-sm text-headline-sm tracking-tight text-on-surface">PIXEL STORE</p>
                        <p class="mt-1 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                            Todo el mundo tecnológico
                        </p>
                    </div>
                </div>

                <div class="text-right">
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Comprobante</p>
                    <p class="mt-1 font-headline-md text-headline-md tabular-nums text-on-surface">{{ $numero }}</p>
                    <p class="mt-1 font-body-sm text-body-sm tabular-nums text-on-surface-variant">
                        {{ $venta->fecha->format('d/m/Y H:i') }}
                    </p>
                </div>
            </header>

            {{-- Cliente y vendedor --}}
            <section class="grid grid-cols-1 gap-6 border-b border-outline-variant/30 px-8 py-5 sm:grid-cols-2">
                <div>
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cliente</p>
                    <p class="mt-1 font-body-md text-body-md text-on-surface">
                        {{ $venta->cliente?->nombre ?? 'Consumidor final' }}
                    </p>
                    @if ($venta->cliente?->nit_ci)
                        <p class="mt-0.5 font-label-md text-label-md text-on-surface-variant">
                            NIT/CI: {{ $venta->cliente->nit_ci }}
                        </p>
                    @endif
                    @if ($venta->cliente?->telefono)
                        <p class="mt-0.5 font-label-md text-label-md text-on-surface-variant">
                            {{ $venta->cliente->telefono }}
                        </p>
                    @endif
                </div>

                <div class="sm:text-right">
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Vendedor</p>
                    <p class="mt-1 font-body-md text-body-md text-on-surface">
                        {{ $venta->vendedor?->name ?? '—' }}
                    </p>
                    <p class="mt-0.5 font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">
                        Estado: {{ $venta->estado }}
                    </p>
                </div>
            </section>

            {{-- Ítems --}}
            <table class="w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-8 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                        <th class="px-3 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cant.</th>
                        <th class="px-3 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Precio</th>
                        <th class="px-8 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15">
                    @foreach ($venta->detalles as $detalle)
                        <tr>
                            <td class="px-8 py-3 text-on-surface">
                                {{ $detalle->producto?->nombre ?? 'Producto #'.$detalle->producto_id }}
                                @if ($detalle->numeroSerie)
                                    <span class="mt-0.5 block font-label-sm text-label-sm text-outline">
                                        SN {{ $detalle->numeroSerie->numero_serie }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right font-label-md text-label-md tabular-nums text-on-surface-variant">
                                {{ $detalle->cantidad }}
                            </td>
                            <td class="px-3 py-3 text-right font-label-md text-label-md tabular-nums text-on-surface-variant">
                                Bs {{ number_format((float) $detalle->precio_unitario, 2, '.', ',') }}
                            </td>
                            <td class="px-8 py-3 text-right font-label-md text-label-md tabular-nums text-on-surface">
                                Bs {{ number_format($detalle->subtotal(), 2, '.', ',') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagos + totales --}}
            <div class="grid grid-cols-1 gap-6 border-t border-outline-variant/30 px-8 py-5 sm:grid-cols-2">
                <div>
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Pagos</p>
                    <ul class="mt-2 space-y-1">
                        @foreach ($venta->pagos as $pago)
                            <li class="flex items-baseline justify-between gap-3 font-body-sm text-body-sm">
                                <span class="text-on-surface-variant">
                                    {{ $pago->metodo }}
                                    @if ($pago->referencia)
                                        · <span class="font-label-md text-label-md text-on-surface">{{ $pago->referencia }}</span>
                                    @endif
                                </span>
                                <span class="tabular-nums text-on-surface">
                                    Bs {{ number_format((float) $pago->monto, 2, '.', ',') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <dl class="space-y-2 font-body-md text-body-md">
                    <div class="flex items-center justify-between">
                        <dt class="text-on-surface-variant">Subtotal</dt>
                        <dd class="tabular-nums text-on-surface">
                            Bs {{ number_format((float) $venta->total + (float) $venta->descuento, 2, '.', ',') }}
                        </dd>
                    </div>

                    @if ((float) $venta->descuento > 0)
                        <div class="flex items-center justify-between">
                            <dt class="text-on-surface-variant">
                                Descuento ({{ rtrim(rtrim(number_format((float) $venta->descuento_porcentaje, 2, '.', ''), '0'), '.') }} %)
                            </dt>
                            <dd class="tabular-nums text-on-surface">
                                − Bs {{ number_format((float) $venta->descuento, 2, '.', ',') }}
                            </dd>
                        </div>
                    @endif

                    <div class="flex items-center justify-between border-t border-outline-variant/15 pt-2">
                        <dt class="font-headline-sm text-headline-sm text-on-surface">TOTAL</dt>
                        <dd class="font-headline-sm text-headline-sm tabular-nums text-on-surface">
                            Bs {{ number_format((float) $venta->total, 2, '.', ',') }}
                        </dd>
                    </div>
                </dl>
            </div>

            @if ($venta->autorizado_por !== null)
                <p class="border-t border-outline-variant/30 px-8 py-4 font-body-sm text-body-sm text-on-surface-variant">
                    Descuento autorizado por {{ $venta->autorizador?->name ?? 'administrador' }}.
                </p>
            @endif

            <footer class="border-t border-outline-variant/30 px-8 py-4 font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">
                Gracias por tu compra · Pixel Store · La Paz, Bolivia
            </footer>
        </article>
    </div>
</body>
</html>
