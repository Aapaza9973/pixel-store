@extends('layouts.app')

@section('title', $cotizacion->numero)
@section('subtitle', 'Detalle de la cotización')

@section('content')
<div class="max-w-4xl space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cotización</p>
            <h1 class="mt-1 font-headline-md text-headline-md text-on-surface">{{ $cotizacion->numero }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.cotizaciones.pdf', $cotizacion) }}"
               class="rounded-obsidian-lg bg-surface-container px-4 py-2 font-body-md text-body-md text-on-surface-variant transition hover:text-on-surface">PDF</a>
            @can('convertir', $cotizacion)
                <form method="POST" action="{{ route('admin.cotizaciones.convertir', $cotizacion) }}" class="inline">
                    @csrf
                    <button type="submit" class="rounded-obsidian-lg bg-primary-container px-4 py-2 font-body-md text-body-md text-on-primary-container transition hover:bg-primary-container/90">Convertir a venta</button>
                </form>
            @endcan
            @can('anular', $cotizacion)
                <form method="POST" action="{{ route('admin.cotizaciones.anular', $cotizacion) }}" class="inline">
                    @csrf
                    <button type="submit" onclick="return confirm('¿Anular esta cotización?')"
                            class="rounded-obsidian-lg bg-error/10 px-4 py-2 font-body-md text-body-md text-error transition hover:bg-error/20">Anular</button>
                </form>
            @endcan
        </div>
    </div>

    {{-- HU-2.1b: envío por correo y compartir por WhatsApp --}}
    @can('ver cotizaciones')
        @php
            $telefono = preg_replace('/\D/', '', (string) $cotizacion->telefono);
            $textoWhatsapp = rawurlencode(
                "Cotización {$cotizacion->numero} de Pixel Store para {$cotizacion->nombre_cliente}. "
                ."Total: Bs ".number_format((float) $cotizacion->total, 2, ',', '.')."."
            );
            $urlWhatsapp = 'https://wa.me/'.($telefono !== '' ? '591'.$telefono : '').'?text='.$textoWhatsapp;
        @endphp

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <form method="POST" action="{{ route('admin.cotizaciones.enviar', $cotizacion) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-[240px] flex-1">
                    <label for="email" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Enviar por correo</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $cotizacion->email) }}"
                           placeholder="cliente@correo.com"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    @error('email')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">
                    Enviar
                </button>
                <a href="{{ $urlWhatsapp }}" target="_blank" rel="noopener"
                   class="rounded-obsidian border border-emerald-500/30 px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-emerald-400 transition hover:bg-emerald-500/10">
                    WhatsApp
                </a>
            </form>
        </div>
    @endcan

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
        <dl class="grid grid-cols-1 gap-4 text-body-md md:grid-cols-2">
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cliente</dt>
                <dd class="mt-1 text-on-surface">{{ $cotizacion->nombre_cliente }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">NIT / CI</dt>
                <dd class="mt-1 text-on-surface">{{ $cotizacion->nit_ci ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Vendedor</dt>
                <dd class="mt-1 text-on-surface">{{ $cotizacion->vendedor?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</dt>
                <dd class="mt-1 text-on-surface">{{ $cotizacion->estado }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Emisión</dt>
                <dd class="mt-1 text-on-surface">{{ $cotizacion->fecha_emision->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Vence</dt>
                <dd class="mt-1 text-on-surface">{{ $cotizacion->fecha_vencimiento->format('d/m/Y') }} ({{ $cotizacion->validez_dias }} días)</dd>
            </div>
        </dl>
    </div>

    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Ítems</h3>
        </div>
        <table class="min-w-full text-body-md">
            <thead class="bg-surface-container-low">
                <tr class="text-left">
                    <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                    <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cantidad</th>
                    <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">P. unitario</th>
                    <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cotizacion->items as $item)
                    <tr class="border-b border-outline-variant/15 last:border-0">
                        <td class="px-4 py-3.5 text-on-surface">{{ $item->nombre }}</td>
                        <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">{{ $item->cantidad }}</td>
                        <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">Bs {{ number_format($item->precio_unitario, 2, '.', ',') }}</td>
                        <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">Bs {{ number_format($item->subtotal(), 2, '.', ',') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-surface-container-low">
                    <td colspan="3" class="px-4 py-3.5 text-right font-label-md text-label-md uppercase tracking-[0.14em] text-outline">Subtotal</td>
                    <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">Bs {{ number_format($cotizacion->subtotal, 2, '.', ',') }}</td>
                </tr>
                <tr class="bg-surface-container-low">
                    <td colspan="3" class="px-4 py-3.5 text-right font-label-md text-label-md uppercase tracking-[0.14em] text-outline">Descuento</td>
                    <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">Bs {{ number_format($cotizacion->descuento, 2, '.', ',') }}</td>
                </tr>
                <tr class="bg-surface-container-low">
                    <td colspan="3" class="px-4 py-3.5 text-right font-label-md text-label-md uppercase tracking-[0.14em] text-outline">Total</td>
                    <td class="px-4 py-3.5 text-right font-headline-sm text-headline-sm tabular-nums text-on-surface">Bs {{ number_format($cotizacion->total, 2, '.', ',') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
