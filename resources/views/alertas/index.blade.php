@extends('layouts.app')

@section('title', 'Alertas de stock')
@section('subtitle', 'Productos con inventario en nivel crítico')

@section('content')
<div class="space-y-6">
    <x-card title="Alertas de stock" :subtitle="$noLeidas.' alertas sin leer'">
        <x-slot:actions>
            @if ($noLeidas > 0)
                <form method="POST" action="{{ route('alertas.marcar-todas') }}">
                    @csrf
                    <x-secondary-button type="submit">Marcar todas como leídas</x-secondary-button>
                </form>
            @endif
        </x-slot:actions>

        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Tipo</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Mensaje</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Fecha</th>
                        <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15">
                    @forelse ($alertas as $alerta)
                        <tr class="transition hover:bg-primary-container/[0.04] {{ $alerta->leida ? '' : 'bg-surface-container-low/40' }}">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    @unless ($alerta->leida)
                                        <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-tertiary" title="Sin leer"></span>
                                    @endunless

                                    @if ($alerta->tipo === 'sin_stock')
                                        <span class="inline-flex items-center gap-1.5 rounded-obsidian border border-error/25 bg-error/10 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-error">
                                            Sin stock
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-obsidian border border-amber-500/25 bg-amber-500/10 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-amber-400">
                                            Bajo stock
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-body-md text-body-md text-on-surface">{{ $alerta->producto?->nombre ?? 'Producto eliminado' }}</p>
                                <p class="font-label-sm text-label-sm text-outline">{{ $alerta->producto?->sku ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-variant">{{ $alerta->mensaje }}</td>
                            <td class="px-4 py-3.5 font-body-sm text-body-sm text-outline" title="{{ $alerta->created_at?->format('d/m/Y H:i') }}">
                                {{ $alerta->created_at?->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($alerta->producto)
                                        @can('ver productos')
                                            <a href="{{ route('admin.productos.show', $alerta->producto_id) }}"
                                               class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-secondary transition hover:text-on-surface">Ver producto</a>
                                        @endcan
                                    @endif

                                    @unless ($alerta->leida)
                                        @can('editar productos')
                                            <form method="POST" action="{{ route('alertas.leida', $alerta) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-secondary transition hover:text-on-surface">
                                                    Marcar leída
                                                </button>
                                            </form>
                                        @endcan
                                    @endunless

                                    @can('editar productos')
                                        <form method="POST" action="{{ route('alertas.destroy', $alerta) }}"
                                              onsubmit="return confirm('¿Eliminar esta alerta?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-error transition hover:text-error/80">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <p class="font-body-md text-body-md text-on-surface-variant">No hay alertas. Todo el inventario está saludable.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $alertas->links() }}
        </div>
    </x-card>
</div>
@endsection
