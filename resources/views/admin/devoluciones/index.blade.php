@extends('layouts.app')

@section('title', 'Devoluciones')
@section('subtitle', 'Solicitudes, aprobación y trazabilidad')

@section('content')
<div class="space-y-6">

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <form method="GET" action="{{ route('admin.devoluciones.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="estado" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</label>
                <select name="estado" id="estado"
                        class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    <option value="">Todas</option>
                    @foreach (['pendiente', 'procesada', 'rechazada', 'anulada'] as $opcion)
                        <option value="{{ $opcion }}" {{ $estado === $opcion ? 'selected' : '' }}>{{ ucfirst($opcion) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">Filtrar</button>
                <a href="{{ route('admin.devoluciones.index') }}" class="rounded-obsidian bg-surface-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">#</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Venta</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Motivo</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Monto</th>
                        <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Registrada</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($devoluciones as $devolucion)
                        <tr class="border-b border-outline-variant/15 transition hover:bg-primary-container/[0.04] last:border-0">
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface">#{{ $devolucion->id }}</td>
                            <td class="px-4 py-3.5">
                                <a href="{{ route('admin.ventas.show', $devolucion->venta_id) }}" class="text-secondary transition hover:text-on-surface">
                                    #{{ str_pad((string) $devolucion->venta_id, 6, '0', STR_PAD_LEFT) }}
                                </a>
                                <span class="block font-body-sm text-body-sm text-outline">{{ $devolucion->venta?->cliente?->nombre ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-variant">{{ $devolucion->motivo }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">{{ \App\Support\Dinero::formatear((float) $devolucion->total_devuelto) }}</td>
                            <td class="px-4 py-3.5 text-center">
                                @php
                                    $color = match ($devolucion->estado) {
                                        'pendiente' => 'border-amber-500/25 bg-amber-500/10 text-amber-400',
                                        'procesada' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
                                        'rechazada' => 'border-error/25 bg-error/10 text-error',
                                        default => 'border-outline-variant/30 bg-surface-container-low text-on-surface-variant',
                                    };
                                @endphp
                                <span class="inline-flex rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] {{ $color }}">
                                    {{ $devolucion->estado }}
                                </span>
                                @if ($devolucion->motivo_rechazo)
                                    <span class="mt-1 block font-body-sm text-body-sm text-outline">{{ $devolucion->motivo_rechazo }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface-variant">{{ $devolucion->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3.5 text-right">
                                @if ($devolucion->estaPendiente())
                                    <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                        @can('aprobar devoluciones')
                                            <form method="POST" action="{{ route('admin.devoluciones.aprobar', $devolucion) }}"
                                                  onsubmit="return confirm('¿Aprobar la devolución #{{ $devolucion->id }}? Se reingresa stock y se revierten puntos.')">
                                                @csrf
                                                <button type="submit" class="rounded-obsidian bg-emerald-500/10 px-3 py-1.5 font-label-sm text-label-sm uppercase tracking-[0.14em] text-emerald-400 transition hover:bg-emerald-500/20">
                                                    Aprobar
                                                </button>
                                            </form>
                                        @endcan

                                        @can('rechazar devoluciones')
                                            <form method="POST" action="{{ route('admin.devoluciones.rechazar', $devolucion) }}" class="flex items-center gap-2">
                                                @csrf
                                                <input type="text" name="motivo" required maxlength="255" placeholder="Motivo del rechazo"
                                                       class="w-40 rounded-obsidian border-outline-variant/40 bg-surface-container-low px-2 py-1.5 font-body-sm text-body-sm text-on-surface">
                                                <button type="submit" class="rounded-obsidian bg-error/10 px-3 py-1.5 font-label-sm text-label-sm uppercase tracking-[0.14em] text-error transition hover:bg-error/20">
                                                    Rechazar
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                @else
                                    <span class="font-body-sm text-body-sm text-outline">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center font-body-md text-body-md text-outline">No hay devoluciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($devoluciones->hasPages())
            <div class="mt-4 px-5 pb-5">{{ $devoluciones->links() }}</div>
        @endif
    </div>
</div>
@endsection
