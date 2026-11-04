@extends('layouts.app')

@section('title', 'Cierre de caja')
@section('subtitle', 'Cierres por turno y control de efectivo')

@section('content')
<div class="space-y-6">

    {{-- Selector de fecha + acciones --}}
    <div class="flex flex-wrap items-end justify-between gap-4 rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <form method="GET" action="{{ route('admin.caja.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="fecha" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    Día
                </label>
                <input type="date" name="fecha" id="fecha" value="{{ $fecha }}"
                       class="rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-primary-container">
            </div>
            <button type="submit"
                    class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">
                Ver día
            </button>
        </form>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.caja.reporte', ['fecha' => $fecha]) }}"
               class="rounded-obsidian border border-outline-variant/40 px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface transition hover:border-secondary hover:text-secondary">
                Reporte diario
            </a>
            <a href="{{ route('admin.caja.create', ['fecha' => $fecha]) }}"
               class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">
                Nuevo cierre
            </a>
        </div>
    </div>

    {{-- Resumen del día --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cobrado en el día</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-on-surface">
                Bs {{ number_format($resumen['total_ventas'], 2, '.', ',') }}
            </p>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant/80">
                {{ count($cierres) }} cierre(s) registrado(s)
            </p>
        </div>

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Efectivo esperado</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-secondary">
                Bs {{ number_format($resumen['efectivo_esperado'], 2, '.', ',') }}
            </p>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant/80">
                Devoluciones: Bs {{ number_format($resumen['total_devoluciones'], 2, '.', ',') }}
            </p>
        </div>

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Diferencia acumulada</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums {{ abs($diferenciaTotal) < 0.005 ? 'text-emerald-400' : 'text-error' }}">
                Bs {{ number_format($diferenciaTotal, 2, '.', ',') }}
            </p>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant/80">
                efectivo real − esperado
            </p>
        </div>
    </div>

    {{-- Desglose del sistema por método --}}
    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <p class="mb-4 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
            Montos del sistema del {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}
        </p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
            @foreach ($resumen['por_metodo'] as $metodo => $monto)
                <div class="rounded-obsidian border border-outline-variant/25 bg-surface-container-low px-3 py-2">
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">{{ $metodo }}</p>
                    <p class="mt-1 font-body-md text-body-md tabular-nums text-on-surface">
                        Bs {{ number_format($monto, 2, '.', ',') }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Listado de cierres --}}
    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Cierres del día</h3>
            <p class="mt-0.5 font-body-sm text-body-sm text-on-surface-variant">Turnos cerrados y diferencia de caja</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Turno</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Vendedor</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Esperado</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Real</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Diferencia</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cierres as $cierre)
                        @php $diferencia = (float) $cierre->diferencia; @endphp
                        <tr class="border-b border-outline-variant/15 last:border-0 hover:bg-primary-container/[0.04]">
                            <td class="px-4 py-3.5">
                                <span class="inline-flex rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-on-surface-variant">
                                    {{ $cierre->turno }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface">{{ $cierre->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">
                                Bs {{ number_format((float) $cierre->efectivo_esperado, 2, '.', ',') }}
                            </td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">
                                Bs {{ number_format((float) $cierre->efectivo_real, 2, '.', ',') }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <span class="inline-flex items-center gap-1.5 rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] tabular-nums
                                    {{ abs($diferencia) < 0.005
                                        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                        : 'border-error/30 bg-error/10 text-error' }}">
                                    Bs {{ number_format($diferencia, 2, '.', ',') }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-variant">{{ $cierre->notas ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center font-body-md text-body-md text-outline">
                                No hay cierres registrados para este día.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
