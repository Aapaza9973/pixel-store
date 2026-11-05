<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante {{ $numero }} — Pixel Store</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* 5.12.2: ticket térmico de 80 mm de ancho, alto variable. */
        @page {
            size: 80mm auto;
            margin: 4mm;
        }

        .ticket {
            width: 80mm;
        }

        @media print {
            html,
            body {
                background: #fff !important;
            }

            .no-print {
                display: none !important;
            }

            .ticket {
                width: auto;
                min-height: 0;
                padding: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-surface font-body-md text-on-surface antialiased">
    <div class="mx-auto w-fit px-4 py-8">

        {{-- Controles de pantalla: nunca salen en papel. --}}
        <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.ventas.create') }}"
               class="inline-flex items-center gap-2 rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-3 py-2 text-body-sm text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
                ← Volver al POS
            </a>

            <div class="flex items-center gap-2">
                <span class="rounded-obsidian bg-primary-container px-3 py-1.5 font-label-sm text-label-sm uppercase tracking-[0.14em] text-on-primary-container">
                    Ticket 80 mm
                </span>
                <a href="{{ route('admin.ventas.comprobante', ['venta' => $venta, 'formato' => 'a4']) }}"
                   class="rounded-obsidian bg-surface-container px-3 py-1.5 font-label-sm text-label-sm uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                    A4
                </a>
                <button type="button" x-on:click="window.print()"
                        class="inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-3 py-2 text-body-sm font-medium text-on-primary-container transition hover:bg-primary-container/90">
                    Imprimir
                </button>
            </div>
        </div>

        <article class="ticket rounded-obsidian-xl border border-outline-variant/30 bg-white px-4 py-5 text-black shadow-2xl">

            <header class="text-center">
                <p class="text-lg font-bold tracking-[0.14em]">PIXEL STORE</p>
                <p class="mt-0.5 text-[10px] uppercase tracking-[0.18em] text-neutral-500">Todo el mundo tecnológico</p>
                <p class="mt-3 text-[11px] uppercase tracking-[0.18em] text-neutral-500">Comprobante de venta</p>
                <p class="mt-0.5 text-base font-semibold tabular-nums">{{ $numero }}</p>
                <p class="mt-0.5 text-[11px] tabular-nums text-neutral-600">{{ $venta->fecha->format('d/m/Y H:i') }}</p>
            </header>

            <div class="my-3 border-t border-dashed border-neutral-300"></div>

            <section class="text-[11px] leading-snug">
                <p><span class="text-neutral-500">Cliente:</span> {{ $venta->cliente?->nombre ?? 'Consumidor final' }}</p>
                @if ($venta->cliente?->nit_ci)
                    <p><span class="text-neutral-500">NIT/CI:</span> {{ $venta->cliente->nit_ci }}</p>
                @endif
                @if ($venta->cliente?->telefono)
                    <p><span class="text-neutral-500">Tel.:</span> {{ $venta->cliente->telefono }}</p>
                @endif
                <p><span class="text-neutral-500">Vendedor:</span> {{ $venta->vendedor?->name ?? '—' }}</p>
                <p><span class="text-neutral-500">Estado:</span> {{ $venta->estado }}</p>
            </section>

            <div class="my-3 border-t border-dashed border-neutral-300"></div>

            <table class="w-full text-[11px]">
                <thead>
                    <tr class="text-neutral-500">
                        <th class="pb-1 text-left font-medium uppercase tracking-[0.08em]">Producto</th>
                        <th class="pb-1 text-right font-medium uppercase tracking-[0.08em]">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($venta->detalles as $detalle)
                        <tr class="border-t border-dashed border-neutral-200 align-top">
                            <td class="py-1 pr-2">
                                <span class="block font-medium">{{ $detalle->producto?->nombre ?? 'Producto #'.$detalle->producto_id }}</span>
                                @if ($detalle->numeroSerie)
                                    <span class="block text-[10px] text-neutral-500">SN {{ $detalle->numeroSerie->numero_serie }}</span>
                                @endif
                                <span class="block text-[10px] text-neutral-500">
                                    {{ $detalle->cantidad }} × Bs {{ number_format((float) $detalle->precio_unitario, 2, '.', ',') }}
                                </span>
                            </td>
                            <td class="py-1 text-right tabular-nums">
                                Bs {{ number_format($detalle->subtotal(), 2, '.', ',') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="my-3 border-t border-dashed border-neutral-300"></div>

            <dl class="space-y-1 text-[11px]">
                <div class="flex items-center justify-between">
                    <dt class="text-neutral-500">Subtotal</dt>
                    <dd class="tabular-nums">Bs {{ number_format((float) $venta->total + (float) $venta->descuento, 2, '.', ',') }}</dd>
                </div>

                @if ((float) $venta->descuento > 0)
                    <div class="flex items-center justify-between">
                        <dt class="text-neutral-500">
                            Descuento ({{ rtrim(rtrim(number_format((float) $venta->descuento_porcentaje, 2, '.', ''), '0'), '.') }} %)
                        </dt>
                        <dd class="tabular-nums">− Bs {{ number_format((float) $venta->descuento, 2, '.', ',') }}</dd>
                    </div>
                @endif

                <div class="flex items-center justify-between border-t border-neutral-300 pt-1.5">
                    <dt class="text-sm font-bold">TOTAL</dt>
                    <dd class="text-sm font-bold tabular-nums">Bs {{ number_format((float) $venta->total, 2, '.', ',') }}</dd>
                </div>
            </dl>

            <div class="my-3 border-t border-dashed border-neutral-300"></div>

            <section class="text-[11px]">
                <p class="uppercase tracking-[0.14em] text-neutral-500">Pagos</p>
                <ul class="mt-1 space-y-0.5">
                    @foreach ($venta->pagos as $pago)
                        <li class="flex items-baseline justify-between gap-2">
                            <span>
                                {{ $pago->metodo }}
                                @if ($pago->referencia)
                                    · {{ $pago->referencia }}
                                @endif
                            </span>
                            <span class="tabular-nums">Bs {{ number_format((float) $pago->monto, 2, '.', ',') }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>

            @if ($venta->autorizado_por !== null)
                <p class="mt-3 text-[10px] text-neutral-600">
                    Descuento autorizado por {{ $venta->autorizador?->name ?? 'administrador' }}.
                </p>
            @endif

            <footer class="mt-4 border-t border-dashed border-neutral-300 pt-3 text-center text-[10px] text-neutral-500">
                <p>Gracias por tu compra</p>
                <p class="mt-0.5 uppercase tracking-[0.14em]">Pixel Store · La Paz, Bolivia</p>
            </footer>
        </article>
    </div>
</body>
</html>
