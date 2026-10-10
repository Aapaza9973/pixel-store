@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Panel de control · ' . now()->translatedFormat('l, d \\d\\e F \\d\\e Y'))

@section('content')
<div class="space-y-6">

    {{-- ===================== KPIs ===================== --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- Productos totales --}}
        <article class="group relative flex flex-col gap-3 overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl transition-all duration-300 hover:border-primary-container/40 hover:bg-surface-container-lowest/80">
            <div class="flex items-start justify-between">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    Productos totales
                </p>
                <div class="flex h-9 w-9 items-center justify-center rounded-obsidian border border-outline-variant/30 bg-surface-container-low/60 text-tertiary transition-colors group-hover:text-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <p class="font-headline-lg text-headline-lg tabular-nums text-on-surface">
                {{ number_format($productosTotales) }}
            </p>
            <p class="font-body-sm text-body-sm text-on-surface-variant/80">
                en catálogo
            </p>
        </article>

        {{-- Stock bajo --}}
        <article class="group relative flex flex-col gap-3 overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl transition-all duration-300 hover:border-primary-container/40 hover:bg-surface-container-lowest/80">
            <div class="flex items-start justify-between">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    Stock bajo
                </p>
                <div class="flex h-9 w-9 items-center justify-center rounded-obsidian border border-outline-variant/30 bg-surface-container-low/60 text-amber-400 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <p class="font-headline-lg text-headline-lg tabular-nums text-amber-400">
                {{ number_format($productosStockBajo) }}
            </p>
            <p class="font-body-sm text-body-sm text-on-surface-variant/80">
                requieren reposición
            </p>
        </article>

        {{-- Productos agotados --}}
        <article class="group relative flex flex-col gap-3 overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl transition-all duration-300 hover:border-primary-container/40 hover:bg-surface-container-lowest/80">
            <div class="flex items-start justify-between">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    Agotados
                </p>
                <div class="flex h-9 w-9 items-center justify-center rounded-obsidian border border-outline-variant/30 bg-surface-container-low/60 text-error transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <p class="font-headline-lg text-headline-lg tabular-nums text-error">
                {{ number_format($productosAgotados) }}
            </p>
            <p class="font-body-sm text-body-sm text-on-surface-variant/80">
                sin stock disponible
            </p>
        </article>

        {{-- Valor del inventario --}}
        <article class="group relative flex flex-col gap-3 overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl transition-all duration-300 hover:border-primary-container/40 hover:bg-surface-container-lowest/80">
            <div class="flex items-start justify-between">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    Valor del inventario
                </p>
                <div class="flex h-9 w-9 items-center justify-center rounded-obsidian border border-outline-variant/30 bg-surface-container-low/60 text-emerald-400 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="font-headline-lg text-headline-lg tabular-nums text-emerald-400">
                Bs {{ number_format($valorInventarioBs, 2) }}
            </p>
            <p class="font-body-sm text-body-sm text-on-surface-variant/80">
                precio × stock
            </p>
        </article>
    </div>

    {{-- ===================== Chart + resumen por ubicación ===================== --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Chart principal (2/3) --}}
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl lg:col-span-2">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Inventario
                    </p>
                    <h3 class="mt-1 font-headline-sm text-headline-sm text-on-surface">
                        Stock por categoría
                    </h3>
                </div>
            </div>

            @if ($stockPorCategoria->isEmpty())
                <p class="py-12 text-center font-body-sm text-body-sm text-outline">
                    Aún no hay stock registrado por categoría.
                </p>
            @else
                <div class="h-72">
                    <canvas id="stockChart"></canvas>
                </div>
            @endif
        </div>

        {{-- Actividad / lista secundaria (1/3) --}}
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <div class="mb-4">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    Ubicaciones
                </p>
                <h3 class="mt-1 font-headline-sm text-headline-sm text-on-surface">
                    Resumen por ubicación
                </h3>
            </div>

            @forelse ($stockPorUbicacion as $item)
                <div class="flex items-center justify-between gap-3 border-b border-outline-variant/15 py-3 last:border-0">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-obsidian border border-outline-variant/30 bg-surface-container-low/60 text-tertiary">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-body-md text-body-md text-on-surface">{{ $item->ubicacion->nombre ?? '—' }}</p>
                            <span class="mt-1 inline-flex rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-2 py-0.5 font-label-sm text-label-sm uppercase tracking-[0.14em] text-on-surface-variant">
                                {{ $item->ubicacion->tipo ?? '—' }}
                            </span>
                        </div>
                    </div>
                    <p class="font-headline-sm text-headline-sm tabular-nums text-on-surface">
                        {{ number_format((int) $item->total_stock) }}
                    </p>
                </div>
            @empty
                <p class="py-12 text-center font-body-sm text-body-sm text-outline">
                    No hay ubicaciones con stock registrado.
                </p>
            @endforelse
        </div>
    </div>

    {{-- ===================== Productos críticos ===================== --}}
    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="flex items-center justify-between border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
            <div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Productos críticos</h3>
                <p class="mt-0.5 font-body-sm text-body-sm text-on-surface-variant">
                    Stock igual o bajo el umbral
                </p>
            </div>
        </div>

        @if ($productosCriticos->isEmpty())
            <p class="py-6 text-center font-body-sm text-body-sm text-outline">
                Todo el inventario está saludable.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-body-md">
                    <thead class="bg-surface-container-low">
                        <tr class="text-left">
                            <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                            <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">SKU</th>
                            <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Categoría</th>
                            <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Stock</th>
                            <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Umbral</th>
                            <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productosCriticos as $producto)
                            <tr class="border-b border-outline-variant/15 transition hover:bg-primary-container/[0.04] last:border-0">
                                <td class="px-4 py-3.5 font-medium text-on-surface">{{ $producto->nombre }}</td>
                                <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface-variant">{{ $producto->sku ?? 'S/N' }}</td>
                                <td class="px-4 py-3.5 text-on-surface-variant">{{ $producto->categoria->nombre ?? '—' }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1.5 rounded-obsidian border border-error/25 bg-error/10 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-error">
                                        {{ $producto->stock }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center font-label-md text-label-md tabular-nums text-on-surface-variant">{{ $producto->umbral_alerta }}</td>
                                <td class="px-4 py-3.5 text-right">
                                    @can('editar productos')
                                        @if (Route::has('admin.productos.edit'))
                                            <a href="{{ route('admin.productos.edit', $producto) }}"
                                               class="font-body-sm text-body-sm text-secondary transition hover:text-on-surface">Reponer</a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('stockChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($stockPorCategoria->pluck('categoria_nombre')),
                datasets: [{
                    label: 'Stock',
                    data: @json($stockPorCategoria->pluck('total_stock')),
                    backgroundColor: 'rgba(37, 99, 235, 0.55)',
                    borderColor: 'rgb(37, 99, 235)',
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { color: '#8d90a0' }, grid: { color: 'rgba(67, 70, 85, 0.35)' } },
                    x: { ticks: { color: '#8d90a0' }, grid: { display: false } }
                }
            }
        });
    }
</script>
@endsection
