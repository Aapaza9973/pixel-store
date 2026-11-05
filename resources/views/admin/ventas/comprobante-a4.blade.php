<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante {{ $numero }} (A4) — Pixel Store</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo/pixel-icon-sm.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* 5.12.2: comprobante en hoja A4. */
        @page {
            size: A4;
            margin: 20mm;
        }

        .hoja {
            width: 210mm;
            min-height: 297mm;
        }

        @media print {
            html,
            body {
                background: #fff !important;
            }

            .no-print {
                display: none !important;
            }

            .hoja {
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
                <a href="{{ route('admin.ventas.comprobante', ['venta' => $venta, 'formato' => 'ticket']) }}"
                   class="rounded-obsidian bg-surface-container px-3 py-1.5 font-label-sm text-label-sm uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                    Ticket 80 mm
                </a>
                <span class="rounded-obsidian bg-primary-container px-3 py-1.5 font-label-sm text-label-sm uppercase tracking-[0.14em] text-on-primary-container">
                    A4
                </span>
                <button type="button" x-on:click="window.print()"
                        class="inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-3 py-2 text-body-sm font-medium text-on-primary-container transition hover:bg-primary-container/90">
                    Imprimir
                </button>
            </div>
        </div>

        <article class="hoja rounded-obsidian-xl border border-outline-variant/30 bg-white px-[20mm] py-[18mm] text-black shadow-2xl">

            {{-- Cabecera: marca + tipo y número de documento. --}}
            <header class="flex items-start justify-between gap-6 border-b border-neutral-300 pb-5">
                <div>
                    <p class="text-2xl font-bold tracking-[0.14em]">PIXEL STORE</p>
                    <p class="mt-1 text-[10px] uppercase tracking-[0.18em] text-neutral-500">Hardware y tecnología</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] uppercase tracking-[0.18em] text-neutral-500">Comprobante de venta</p>
                    <p class="mt-1 text-xl font-semibold tabular-nums text-blue-600">{{ $numero }}</p>
                    <p class="mt-1 text-[11px] tabular-nums text-neutral-600">{{ $venta->fecha->format('d/m/Y H:i') }}</p>
                </div>
            </header>

            {{-- Cliente y datos de la venta. --}}
            <section class="grid grid-cols-2 gap-8 border-b border-neutral-300 py-5 text-[12px]">
                <div>
                    <p class="mb-1 text-[9px] uppercase tracking-[0.18em] text-neutral-500">Cliente</p>
                    <p class="font-medium">{{ $venta->cliente?->nombre ?? 'Consumidor final' }}</p>
                    @if ($venta->cliente?->nit_ci)
                        <p class="mt-0.5 text-neutral-600">NIT/CI: {{ $venta->cliente->nit_ci }}</p>
                    @endif
                    @if ($venta->cliente?->telefono)
                        <p class="mt-0.5 text-neutral-600">Tel.: {{ $venta->cliente->telefono }}</p>
                    @endif
                    @if ($venta->cliente?->email)
                        <p class="mt-0.5 text-neutral-600">{{ $venta->cliente->email }}</p>
                    @endif
                </div>

                <div>
                    <p class="mb-1 text-[9px] uppercase tracking-[0.18em] text-neutral-500">Datos de la venta</p>
                    <p>Vendedor: {{ $venta->vendedor?->name ?? '—' }}</p>
                    <p class="mt-0.5 text-neutral-600">Estado: {{ $venta->estado }}</p>
                    <p class="mt-0.5 text-neutral-600">Ítems: {{ $venta->detalles->count() }}</p>
                    @if ($venta->cliente?->nombre)
                        <p class="mt-0.5 text-neutral-600">Documento: {{ $numero }}</p>
                    @endif
                </div>
            </section>

            {{-- Detalle de ítems. --}}
            <table class="mt-4 w-full border-collapse text-[11px]">
                <thead>
                    <tr class="bg-neutral-100 text-[9px] uppercase tracking-[0.08em] text-neutral-600">
                        <th class="px-3 py-2 text-left font-medium">Producto</th>
                        <th class="w-16 px-3 py-2 text-center font-medium">Cantidad</th>
                        <th class="w-28 px-3 py-2 text-right font-medium">Precio unit.</th>
                        <th class="w-28 px-3 py-2 text-right font-medium">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($venta->detalles as $detalle)
                        <tr class="border-b border-neutral-200 align-top">
                            <td class="px-3 py-2">
                                {{ $detalle->producto?->nombre ?? 'Producto #'.$detalle->producto_id }}
                                @if ($detalle->numeroSerie)
                                    <span class="block text-[10px] text-neutral-500">SN {{ $detalle->numeroSerie->numero_serie }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center tabular-nums">{{ $detalle->cantidad }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">
                                Bs {{ number_format((float) $detalle->precio_unitario, 2, '.', ',') }}
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums">
                                Bs {{ number_format($detalle->subtotal(), 2, '.', ',') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-6 grid grid-cols-2 gap-8">
                {{-- Pagos. --}}
                <div class="text-[11px]">
                    <p class="mb-1 text-[9px] uppercase tracking-[0.18em] text-neutral-500">Pagos</p>
                    <ul class="space-y-1">
                        @foreach ($venta->pagos as $pago)
                            <li class="flex items-baseline justify-between gap-3">
                                <span class="text-neutral-600">
                                    {{ $pago->metodo }}
                                    @if ($pago->referencia)
                                        · {{ $pago->referencia }}
                                    @endif
                                </span>
                                <span class="tabular-nums">Bs {{ number_format((float) $pago->monto, 2, '.', ',') }}</span>
                            </li>
                        @endforeach
                    </ul>

                    @if ($venta->autorizado_por !== null)
                        <p class="mt-3 text-[10px] text-neutral-600">
                            Descuento autorizado por {{ $venta->autorizador?->name ?? 'administrador' }}.
                        </p>
                    @endif
                </div>

                {{-- Totales. --}}
                <dl class="space-y-2 text-[12px]">
                    <div class="flex items-center justify-between">
                        <dt class="text-neutral-600">Subtotal</dt>
                        <dd class="tabular-nums">Bs {{ number_format((float) $venta->total + (float) $venta->descuento, 2, '.', ',') }}</dd>
                    </div>

                    @if ((float) $venta->descuento > 0)
                        <div class="flex items-center justify-between">
                            <dt class="text-neutral-600">
                                Descuento ({{ rtrim(rtrim(number_format((float) $venta->descuento_porcentaje, 2, '.', ''), '0'), '.') }} %)
                            </dt>
                            <dd class="tabular-nums">− Bs {{ number_format((float) $venta->descuento, 2, '.', ',') }}</dd>
                        </div>
                    @endif

                    <div class="flex items-center justify-between border-t border-neutral-400 pt-2">
                        <dt class="text-base font-bold">TOTAL</dt>
                        <dd class="text-base font-bold tabular-nums">Bs {{ number_format((float) $venta->total, 2, '.', ',') }}</dd>
                    </div>
                </dl>
            </div>

            <footer class="mt-10 border-t border-neutral-300 pt-3 text-[9px] text-neutral-500">
                <p>Gracias por tu compra · Pixel Store · La Paz, Bolivia</p>
                <p class="mt-0.5">Documento generado el {{ now()->format('d/m/Y H:i') }} · {{ $numero }}</p>
            </footer>
        </article>
    </div>
</body>
</html>
