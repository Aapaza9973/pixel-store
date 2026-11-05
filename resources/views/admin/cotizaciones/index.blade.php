@extends('layouts.app')

@section('title', 'Cotizaciones')
@section('subtitle', 'Cotizaciones formales para clientes')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <p class="font-body-sm text-body-sm text-on-surface-variant">
            Total: <span class="font-semibold text-on-surface">{{ $cotizaciones->total() }}</span> cotizaciones
        </p>
        @can('crear cotizaciones')
            <a href="{{ route('admin.cotizaciones.create') }}"
               class="inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                Nueva cotización
            </a>
        @endcan
    </div>

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <form method="GET" action="{{ route('admin.cotizaciones.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label for="buscar" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Buscar</label>
                <input type="text" name="buscar" id="buscar" value="{{ request('buscar') }}"
                       placeholder="Número o cliente..."
                       class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
            </div>
            <div>
                <label for="estado" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</label>
                <select name="estado" id="estado"
                        class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    <option value="">Todos</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado }}" {{ request('estado') === $estado ? 'selected' : '' }}>{{ $estado }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">Filtrar</button>
                <a href="{{ route('admin.cotizaciones.index') }}" class="rounded-obsidian bg-surface-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Número</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cliente</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                        <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Emisión</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cotizaciones as $cotizacion)
                        <tr class="border-b border-outline-variant/15 transition hover:bg-primary-container/[0.04] last:border-0">
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface">{{ $cotizacion->numero }}</td>
                            <td class="px-4 py-3.5 text-on-surface">{{ $cotizacion->nombre_cliente }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">Bs {{ number_format($cotizacion->total, 2, '.', ',') }}</td>
                            <td class="px-4 py-3.5 text-center">
                                @php
                                    $color = match ($cotizacion->estado) {
                                        'Vigente' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25',
                                        'Vencida' => 'bg-amber-500/10 text-amber-400 border-amber-500/25',
                                        'Convertida' => 'bg-primary-container/10 text-secondary border-primary-container/25',
                                        'Anulada' => 'bg-error/10 text-error border-error/25',
                                        default => 'bg-surface-container-low text-on-surface-variant border-outline-variant/30',
                                    };
                                @endphp
                                <span class="inline-flex rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] {{ $color }}">{{ $cotizacion->estado }}</span>
                            </td>
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface-variant">{{ $cotizacion->fecha_emision->format('d/m/Y') }}</td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('admin.cotizaciones.show', $cotizacion) }}" class="font-body-sm text-body-sm text-secondary transition hover:text-on-surface">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center font-body-md text-body-md text-outline">No hay cotizaciones.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cotizaciones->hasPages())
            <div class="mt-4 px-5 pb-5">{{ $cotizaciones->links() }}</div>
        @endif
    </div>
</div>
@endsection
