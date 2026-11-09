@extends('layouts.app')

@section('title', 'Pedidos web')
@section('subtitle', 'Ventas del catálogo público')

@section('content')
<div class="space-y-6" x-data="{ cancelarAbierto: false, cancelarUrl: '', cancelarNumero: '', cancelarUnidades: 0 }">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ventas</p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">Pedidos web</h2>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                {{ $pedidos->total() }} pedido(s) · {{ $pendientes }} pendiente(s) de cobro
            </p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low/40 p-4">
        <form method="GET" action="{{ route('admin.pedidos-web.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[220px] flex-1">
                <label for="buscar" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Buscar</label>
                <input type="text" name="buscar" id="buscar" value="{{ $filtros['buscar'] ?? '' }}"
                       placeholder="Cliente, NIT, email o teléfono"
                       class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
            </div>

            <div>
                <label for="estado" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</label>
                <select name="estado" id="estado"
                        class="rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    <option value="">Todos</option>
                    @foreach (['Pendiente', 'Pagado', 'Cancelada'] as $estado)
                        <option value="{{ $estado }}" @selected(($filtros['estado'] ?? '') === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="rounded-obsidian-lg bg-primary-container px-4 py-2 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                    Filtrar
                </button>
                <a href="{{ route('admin.pedidos-web.index') }}"
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
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Pedido</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cliente</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Contacto</th>
                        <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                        <th class="px-4 py-3 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</th>
                        <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15">
                    @forelse ($pedidos as $pedido)
                        @php
                            $numero = '#'.str_pad((string) $pedido->id, 6, '0', STR_PAD_LEFT);
                            $claseEstado = match ($pedido->estado) {
                                'Pendiente' => 'border-amber-500/25 bg-amber-500/10 text-amber-400',
                                'Pagado' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
                                default => 'border-outline-variant/30 bg-surface-container-low text-outline',
                            };
                            $unidades = (int) $pedido->detalles->sum('cantidad');
                        @endphp

                        <tr class="transition hover:bg-primary-container/[0.04]">
                            <td class="px-4 py-3.5">
                                <a href="{{ route('admin.pedidos-web.show', $pedido) }}"
                                   class="font-label-md text-label-md text-secondary transition hover:text-on-surface">
                                    {{ $numero }}
                                </a>
                                <p class="font-label-sm text-label-sm text-outline">{{ $pedido->fecha?->format('d/m/Y H:i') }}</p>
                            </td>

                            <td class="px-4 py-3.5">
                                <p class="text-on-surface">{{ $pedido->cliente?->nombre ?? 'Sin cliente' }}</p>
                                <p class="font-label-sm text-label-sm text-outline">
                                    NIT/CI {{ $pedido->cliente?->nit_ci ?? '—' }}
                                </p>
                            </td>

                            <td class="px-4 py-3.5">
                                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $pedido->cliente?->email ?? '—' }}</p>
                                <p class="font-label-sm text-label-sm text-outline">{{ $pedido->cliente?->telefono ?? '—' }}</p>
                            </td>

                            <td class="px-4 py-3.5 text-right font-label-md text-label-md tabular-nums text-on-surface">
                                Bs {{ number_format((float) $pedido->total, 2, ',', '.') }}
                                <p class="font-label-sm text-label-sm text-outline">{{ $unidades }} u.</p>
                            </td>

                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] {{ $claseEstado }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ $pedido->estado }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5 text-right">
                                <div class="inline-flex flex-wrap items-center justify-end gap-3">
                                    <a href="{{ route('admin.pedidos-web.show', $pedido) }}"
                                       class="text-body-sm text-on-surface-variant transition hover:text-on-surface">
                                        Ver
                                    </a>

                                    @if ($pedido->estado === 'Pendiente')
                                        @can('editar ventas')
                                            <form method="POST" action="{{ route('admin.pedidos-web.pagar', $pedido) }}"
                                                  onsubmit="return confirm('¿Marcar el pedido {{ $numero }} como pagado? Se registrará el ingreso.')"
                                                  class="inline">
                                                @csrf
                                                <button type="submit" class="text-body-sm text-emerald-400 transition hover:text-emerald-300">
                                                    Marcar pagado
                                                </button>
                                            </form>
                                        @endcan

                                        @can('cancelar ventas')
                                            <button type="button"
                                                    x-on:click="cancelarAbierto = true; cancelarUrl = '{{ route('admin.pedidos-web.cancelar', $pedido) }}'; cancelarNumero = '{{ $numero }}'; cancelarUnidades = {{ $unidades }}"
                                                    class="text-body-sm text-error transition hover:text-error/80">
                                                Cancelar
                                            </button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center font-body-sm text-body-sm text-on-surface-variant">
                                No hay pedidos web que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pedidos->hasPages())
            <div class="border-t border-outline-variant/15 px-4 py-3">
                {{ $pedidos->links() }}
            </div>
        @endif
    </div>

    {{-- Modal de cancelación --}}
    <div x-cloak x-show="cancelarAbierto" x-on:keydown.escape.window="cancelarAbierto = false"
         class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="cancelar-titulo">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" x-on:click="cancelarAbierto = false"></div>

        <div class="absolute left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-obsidian-xl border border-outline-variant/40 bg-surface-container-high p-6 shadow-2xl">
            <h3 id="cancelar-titulo" class="font-headline-sm text-headline-sm text-on-surface">
                Cancelar pedido <span x-text="cancelarNumero"></span>
            </h3>

            <p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">
                El pedido pasará a <span class="text-on-surface">Cancelada</span> y se reingresarán
                <span class="tabular-nums text-on-surface" x-text="cancelarUnidades"></span> unidad(es) al stock.
                Esta acción no se puede deshacer.
            </p>

            <form method="POST" x-bind:action="cancelarUrl" class="mt-5 flex justify-end gap-3">
                @csrf

                <button type="button" x-on:click="cancelarAbierto = false"
                        class="rounded-obsidian-lg border border-outline-variant/40 px-4 py-2 text-body-md text-on-surface-variant transition hover:text-on-surface">
                    Volver
                </button>

                <button type="submit"
                        class="rounded-obsidian-lg bg-error/90 px-4 py-2 text-body-md font-medium text-white transition hover:bg-error">
                    Cancelar pedido
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
