@extends('layouts.app')

@section('title', 'Comparativa mensual')
@section('subtitle', 'Últimos seis meses de ventas')

@section('content')
<div class="space-y-6">

    @php
        $totalGeneral = collect($filas)->sum(fn (array $fila) => (float) ($fila['total_ventas'] ?? 0));
        $cantidadGeneral = collect($filas)->sum(fn (array $fila) => (int) ($fila['cantidad_ventas'] ?? 0));
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Meses</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-on-surface">{{ count($filas) }}</p>
        </div>
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ventas</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-secondary">{{ $cantidadGeneral }}</p>
        </div>
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-on-surface">Bs {{ number_format($totalGeneral, 2, '.', ',') }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Mes</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ventas</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Variación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $indice => $fila)
                        @php
                            $total = (float) ($fila['total_ventas'] ?? 0);
                            $anterior = $indice > 0 ? (float) ($filas[$indice - 1]['total_ventas'] ?? 0) : null;
                            $variacion = $anterior === null || $anterior == 0.0
                                ? null
                                : round((($total - $anterior) / $anterior) * 100, 1);
                        @endphp
                        <tr class="border-b border-outline-variant/15 transition hover:bg-primary-container/[0.04] last:border-0">
                            <td class="px-4 py-3.5 text-on-surface">{{ $fila['periodo'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">{{ $fila['cantidad_ventas'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">Bs {{ number_format($total, 2, '.', ',') }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums">
                                @if ($variacion === null)
                                    <span class="text-outline">—</span>
                                @else
                                    <span class="{{ $variacion >= 0 ? 'text-emerald-400' : 'text-error' }}">
                                        {{ $variacion > 0 ? '+' : '' }}{{ number_format($variacion, 1, '.', ',') }} %
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center font-body-md text-body-md text-outline">Sin ventas en el período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('admin.reportes.indicadores') }}" class="inline-block font-body-sm text-body-sm text-secondary transition hover:text-on-surface">
        ← Ver indicadores
    </a>
</div>
@endsection
