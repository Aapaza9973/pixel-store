@extends('layouts.app')

@section('title', 'Serie '.$serie->numero_serie)
@section('subtitle', 'Detalle y garantía')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Número de serie</p>
            <h2 class="mt-1 font-headline-md text-headline-md font-mono text-on-surface">{{ $serie->numero_serie }}</h2>
        </div>
        <div class="flex items-center gap-3">
            @can('editar numeros-serie')
                <a href="{{ route('admin.series.edit', $serie) }}"
                   class="rounded-obsidian-lg bg-surface-container px-4 py-2 font-body-md text-body-md text-on-surface-variant transition hover:text-on-surface">
                    Editar
                </a>
                <form method="POST" action="{{ route('admin.series.destroy', $serie) }}"
                      onsubmit="return confirm('¿Eliminar la serie {{ $serie->numero_serie }}?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-obsidian-lg bg-error/10 px-4 py-2 font-body-md text-body-md text-error transition hover:bg-error/20">
                        Eliminar
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <h3 class="mb-4 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado e inventario</h3>
            <dl class="space-y-3 font-body-md text-body-md">
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Estado</dt>
                    <dd class="font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface">{{ $serie->estado }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Producto</dt>
                    <dd class="text-on-surface">{{ $serie->producto?->nombre ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">SKU</dt>
                    <dd class="font-label-md text-label-md text-on-surface">{{ $serie->producto?->sku ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Costo</dt>
                    <dd class="tabular-nums text-on-surface">
                        {{ $serie->costo_unitario !== null ? 'Bs '.number_format((float) $serie->costo_unitario, 2, '.', ',') : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Ubicación</dt>
                    <dd class="text-on-surface">{{ $serie->ubicacion?->nombre ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Ingreso</dt>
                    <dd class="text-on-surface">{{ $serie->fecha_ingreso?->format('d/m/Y') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <h3 class="mb-4 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Garantía</h3>
            <dl class="space-y-3 font-body-md text-body-md">
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Venta</dt>
                    <dd class="text-on-surface">
                        @if ($serie->venta)
                            <a href="{{ route('admin.ventas.show', $serie->venta) }}" class="text-secondary transition hover:text-on-surface">
                                {{ $serie->venta->numero ?? '#'.$serie->venta->id }}
                            </a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Fecha de venta</dt>
                    <dd class="text-on-surface">{{ $serie->fecha_venta?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Cliente</dt>
                    <dd class="text-on-surface">{{ $serie->cliente?->nombre ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Garantía hasta</dt>
                    <dd class="font-label-md text-label-md text-on-surface">{{ $serie->garantia_hasta?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Vigente</dt>
                    <dd>
                        @if ($serie->garantia_hasta === null)
                            <span class="font-body-sm text-body-sm text-outline">Sin garantía registrada</span>
                        @elseif ($serie->garantia_hasta->isFuture() || $serie->garantia_hasta->isToday())
                            <span class="inline-flex rounded-obsidian border border-emerald-500/25 bg-emerald-500/10 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-emerald-400">Vigente</span>
                        @else
                            <span class="inline-flex rounded-obsidian border border-error/25 bg-error/10 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-error">Vencida</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    @if ($serie->notas)
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <h3 class="mb-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Notas</h3>
            <p class="font-body-md text-body-md text-on-surface-variant">{{ $serie->notas }}</p>
        </div>
    @endif

    <a href="{{ route('admin.series.index') }}" class="inline-block font-body-sm text-body-sm text-secondary transition hover:text-on-surface">
        ← Volver al listado
    </a>
</div>
@endsection
