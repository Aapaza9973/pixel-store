@extends('layouts.app')

@section('title', 'Alertas de stock')
@section('subtitle', 'Productos con inventario en nivel crítico')

@section('content')
<div class="space-y-6">
    <x-card title="Alertas de stock" subtitle="{{ $noLeidas }} alertas sin leer">
        <x-slot:actions>
            @if ($noLeidas > 0)
                <form method="POST" action="{{ route('alertas.marcar-todas') }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-medium rounded-lg transition">
                        Marcar todas como leídas
                    </button>
                </form>
            @endif
        </x-slot:actions>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-3 py-3">Tipo</th>
                        <th class="px-3 py-3">Producto</th>
                        <th class="px-3 py-3">Mensaje</th>
                        <th class="px-3 py-3">Fecha</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($alertas as $alerta)
                        <tr class="{{ $alerta->leida ? '' : 'bg-slate-800/40' }} hover:bg-slate-800/60 transition">
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    @unless ($alerta->leida)
                                        <span class="w-2 h-2 rounded-full bg-blue-400 flex-shrink-0" title="Sin leer"></span>
                                    @endunless

                                    @if ($alerta->tipo === 'sin_stock')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-500/10 text-red-400">
                                            Sin stock
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-amber-500/10 text-amber-400">
                                            Bajo stock
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <p class="text-white font-medium">{{ $alerta->producto?->nombre ?? 'Producto eliminado' }}</p>
                                <p class="text-xs text-slate-500">{{ $alerta->producto?->sku ?? '—' }}</p>
                            </td>
                            <td class="px-3 py-3 text-slate-300">{{ $alerta->mensaje }}</td>
                            <td class="px-3 py-3 text-slate-400" title="{{ $alerta->created_at?->format('d/m/Y H:i') }}">
                                {{ $alerta->created_at?->diffForHumans() }}
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($alerta->producto)
                                        @can('ver productos')
                                            <a href="{{ route('admin.productos.show', $alerta->producto_id) }}"
                                               class="text-xs text-slate-400 hover:text-white transition">Ver producto</a>
                                        @endcan
                                    @endif

                                    @unless ($alerta->leida)
                                        @can('editar productos')
                                            <form method="POST" action="{{ route('alertas.leida', $alerta) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-xs text-blue-400 hover:text-blue-300 transition">
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
                                            <button type="submit" class="text-xs text-red-400 hover:text-red-300 transition">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-10 text-center text-sm text-slate-500">
                                No hay alertas. Todo el inventario está saludable.
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
