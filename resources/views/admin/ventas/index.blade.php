@extends('layouts.app')

@section('title', 'Historial de Ventas')
@section('subtitle', 'Ventas y pedidos registrados')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ventas</p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">Historial de Ventas</h2>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                {{ $ventas->total() }} venta(s) registradas
            </p>
        </div>

        @can('crear ventas')
            @if (Route::has('admin.ventas.create'))
                <a href="{{ route('admin.ventas.create') }}"
                   class="inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-4 py-2.5 text-body-md font-medium text-on-primary-container shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_0_20px_rgba(37,99,235,0.35)] transition hover:bg-primary-container/90">
                    + Nueva Venta
                </a>
            @endif
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low/40 p-4">
        <form method="GET" action="{{ route('admin.ventas.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[220px] flex-1">
                <label for="buscar" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Buscar</label>
                <input type="text" name="buscar" id="buscar" value="{{ $filtros['buscar'] ?? '' }}"
                       placeholder="Nro de venta o nombre de cliente"
                       class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
            </div>

            <div>
                <label for="origen" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Origen</label>
                <select name="origen" id="origen"
                        class="rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    <option value="">Todos</option>
                    <option value="pos" @selected(($filtros['origen'] ?? '') === 'pos')>POS</option>
                    <option value="web" @selected(($filtros['origen'] ?? '') === 'web')>Web</option>
                </select>
            </div>

            <div>
                <label for="estado" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</label>
                <select name="estado" id="estado"
                        class="rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    <option value="">Todos</option>
                    @foreach (['Pagado', 'Pendiente', 'Cancelada'] as $estado)
                        <option value="{{ $estado }}" @selected(($filtros['estado'] ?? '') === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="rounded-obsidian-lg bg-primary-container px-4 py-2 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                    Filtrar
                </button>
                <a href="{{ route('admin.ventas.index') }}"
                   class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2 text-body-md text-on-surface-variant transition hover:text-on-surface">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Nro</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Fecha</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cliente</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Vendedor</th>
                        <th class="px-4 py-3 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Origen</th>
                        <th class="px-4 py-3 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</th>
                        <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                        <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15">
                    @forelse ($ventas as $venta)
                        @php
                            $numero = '#'.str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT);
                            $claseEstado = match ($venta->estado) {
                                'Pagado' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
                                'Pendiente' => 'border-amber-500/25 bg-amber-500/10 text-amber-400',
                                default => 'border-outline-variant/30 bg-surface-container-low text-outline',
                            };
                            $esWeb = $venta->origen === 'web';
                        @endphp

                        <tr class="transition hover:bg-primary-container/[0.04]">
                            <td class="px-4 py-3.5">
                                <a href="{{ route('admin.ventas.show', $venta) }}"
                                   class="font-label-md text-label-md text-secondary transition hover:text-on-surface">
                                    {{ $numero }}
                                </a>
                            </td>

                            <td class="px-4 py-3.5 text-on-surface-variant">
                                {{ $venta->fecha?->format('d/m/Y H:i') ?? '—' }}
                            </td>

                            <td class="px-4 py-3.5 text-on-surface">
                                {{ $venta->cliente?->nombre ?? 'Consumidor Final' }}
                            </td>

                            <td class="px-4 py-3.5 text-on-surface-variant">
                                {{ $venta->vendedor?->name ?? ($esWeb ? 'Web' : '—') }}
                            </td>

                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex items-center rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em]
                                    {{ $esWeb
                                        ? 'border-primary-container/40 bg-primary-container/10 text-secondary'
                                        : 'border-outline-variant/30 bg-surface-container-low text-on-surface-variant' }}">
                                    {{ $esWeb ? 'Web' : 'POS' }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] {{ $claseEstado }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ $venta->estado }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5 text-right font-label-md text-label-md tabular-nums text-on-surface">
                                {{ \App\Support\Dinero::formatear((float) $venta->total) }}
                            </td>

                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('admin.ventas.show', $venta) }}"
                                   class="text-body-sm text-on-surface-variant transition hover:text-on-surface">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center font-body-sm text-body-sm text-on-surface-variant">
                                No hay ventas que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ventas->hasPages())
            <div class="border-t border-outline-variant/15 px-4 py-3">
                {{ $ventas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
