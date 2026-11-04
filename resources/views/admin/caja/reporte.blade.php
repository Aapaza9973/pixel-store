@extends('layouts.app')

@section('title', 'Reporte diario')
@section('subtitle', 'Cobros, ventas y cierres del día')

@section('content')
<div class="space-y-6">

    {{-- Selector de día --}}
    <div class="flex flex-wrap items-end justify-between gap-4 rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <form method="GET" action="{{ route('admin.caja.reporte') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="fecha" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    Día
                </label>
                <input type="date" name="fecha" id="fecha" value="{{ $dia }}"
                       class="rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-primary-container">
            </div>
            <button type="submit"
                    class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">
                Ver reporte
            </button>
        </form>

        <a href="{{ route('admin.caja.index', ['fecha' => $dia]) }}"
           class="rounded-obsidian border border-outline-variant/40 px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface transition hover:border-secondary hover:text-secondary">
            Volver a caja
        </a>
    </div>

    {{-- Totales por método --}}
    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    {{ \Illuminate\Support\Carbon::parse($dia)->format('d/m/Y') }}
                </p>
                <h3 class="mt-1 font-headline-sm text-headline-sm text-on-surface">Totales por método de pago</h3>
            </div>
            <div class="text-right">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total cobrado</p>
                <p class="mt-1 font-headline-md text-headline-md tabular-nums text-on-surface">
                    Bs {{ number_format($resumen['total_ventas'], 2, '.', ',') }}
                </p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
            @foreach ($resumen['por_metodo'] as $metodo => $monto)
                <div class="rounded-obsidian border border-outline-variant/25 bg-surface-container-low px-3 py-2">
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">{{ $metodo }}</p>
                    <p class="mt-1 font-body-md text-body-md tabular-nums text-on-surface">
                        Bs {{ number_format($monto, 2, '.', ',') }}
                    </p>
                </div>
            @endforeach
        </div>

        <p class="mt-4 font-body-sm text-body-sm text-on-surface-variant">
            Devoluciones del día: Bs {{ number_format($resumen['total_devoluciones'], 2, '.', ',') }}
            · Efectivo esperado: Bs {{ number_format($resumen['efectivo_esperado'], 2, '.', ',') }}
        </p>
    </div>

    {{-- Cierres del día --}}
    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <h3 class="font-headline-sm text-headline-sm text-on-surface">Cierres de caja</h3>

        @forelse ($cierres as $cierre)
            <div class="mt-4 border-b border-outline-variant/15 pb-4 last:border-0 last:pb-0">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-body-md text-body-md text-on-surface">
                            {{ ucfirst($cierre->turno) }} · {{ $cierre->user?->name ?? '—' }}
                        </p>
                        <p class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">
                            Cerrado {{ $cierre->created_at?->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    @php $diferencia = (float) $cierre->diferencia; @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] tabular-nums
                        {{ abs($diferencia) < 0.005
                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                            : 'border-error/30 bg-error/10 text-error' }}">
                        Diferencia Bs {{ number_format($diferencia, 2, '.', ',') }}
                    </span>
                </div>

                <p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">
                    Efectivo esperado Bs {{ number_format((float) $cierre->efectivo_esperado, 2, '.', ',') }}
                    · real Bs {{ number_format((float) $cierre->efectivo_real, 2, '.', ',') }}
                </p>

                @if ($cierre->pagos->isNotEmpty())
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($cierre->pagos as $pago)
                            <span class="rounded-obsidian border border-outline-variant/25 bg-surface-container-low px-2 py-1 font-label-sm text-label-sm text-on-surface-variant">
                                {{ $pago->metodo }}: Bs {{ number_format((float) $pago->monto_real, 2, '.', ',') }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="mt-4 font-body-sm text-body-sm text-outline">Todavía no hay cierres para este día.</p>
        @endforelse
    </div>

    {{-- Ventas del día --}}
    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Ventas cobradas</h3>
            <p class="mt-0.5 font-body-sm text-body-sm text-on-surface-variant">
                Pagadas, parcial o totalmente devueltas
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Venta</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Hora</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Vendedor</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Pago</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventas as $venta)
                        <tr class="border-b border-outline-variant/15 last:border-0 hover:bg-primary-container/[0.04]">
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface">
                                #{{ str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-4 py-3.5 tabular-nums text-on-surface-variant">
                                {{ $venta->fecha?->format('H:i') }}
                            </td>
                            <td class="px-4 py-3.5 text-on-surface">{{ $venta->vendedor?->name ?? '—' }}</td>
                            <td class="px-4 py-3.5 text-on-surface-variant">
                                {{ $venta->pagos->pluck('metodo')->unique()->implode(', ') ?: '—' }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-on-surface-variant">
                                    {{ str_replace('_', ' ', $venta->estado) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">
                                Bs {{ number_format((float) $venta->total, 2, '.', ',') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center font-body-md text-body-md text-outline">
                                No hay ventas cobradas en este día.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
