@extends('layouts.app')

@section('title', 'Compras')
@section('subtitle', 'Órdenes de compra a proveedores')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <p class="font-body-sm text-body-sm text-on-surface-variant">
            Total: <span class="font-semibold text-on-surface">{{ $ordenes->total() }}</span> órdenes
        </p>
        @can('crear ordenes-compra')
            <a href="{{ route('admin.compras.create') }}"
               class="inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                Nueva orden
            </a>
        @endcan
    </div>

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <form method="GET" action="{{ route('admin.compras.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="estado" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</label>
                <select name="estado" id="estado"
                        class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    <option value="">Todos</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado }}" {{ request('estado') === $estado ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $estado)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[200px] flex-1">
                <label for="proveedor_id" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Proveedor</label>
                <select name="proveedor_id" id="proveedor_id"
                        class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    <option value="">Todos</option>
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" {{ (string) request('proveedor_id') === (string) $proveedor->id ? 'selected' : '' }}>
                            {{ $proveedor->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="desde" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Desde</label>
                <input type="date" name="desde" id="desde" value="{{ request('desde') }}"
                       class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
            </div>

            <div>
                <label for="hasta" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Hasta</label>
                <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}"
                       class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-primary-container transition hover:bg-primary-container/85">
                    Filtrar
                </button>
                <a href="{{ route('admin.compras.index') }}"
                   class="rounded-obsidian bg-surface-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Número</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Proveedor</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Emisión</th>
                        <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ordenes as $orden)
                        <tr class="border-b border-outline-variant/15 transition hover:bg-primary-container/[0.04] last:border-0">
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface">{{ $orden->numero }}</td>
                            <td class="px-4 py-3.5 text-on-surface">{{ $orden->proveedor?->nombre ?? 'Proveedor eliminado' }}</td>
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface-variant">
                                {{ ($orden->fecha_emision ?? $orden->fecha)?->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @php
                                    $color = match ($orden->estado) {
                                        'borrador' => 'bg-surface-container-low text-on-surface-variant border-outline-variant/30',
                                        'enviada' => 'bg-primary-container/10 text-secondary border-primary-container/25',
                                        'recibida_parcial' => 'bg-amber-500/10 text-amber-400 border-amber-500/25',
                                        'recibida_total' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25',
                                        'cancelada' => 'bg-error/10 text-error border-error/25',
                                        default => 'bg-surface-container-low text-on-surface-variant border-outline-variant/30',
                                    };
                                @endphp
                                <span class="inline-flex rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] {{ $color }}">
                                    {{ ucfirst(str_replace('_', ' ', $orden->estado)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">
                                Bs {{ number_format((float) $orden->total, 2, '.', ',') }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('admin.compras.show', $orden) }}"
                                   class="font-body-sm text-body-sm text-secondary transition hover:text-on-surface">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center font-body-md text-body-md text-outline">
                                No hay órdenes de compra.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ordenes->hasPages())
            <div class="mt-4 px-5 pb-5">{{ $ordenes->links() }}</div>
        @endif
    </div>
</div>
@endsection
