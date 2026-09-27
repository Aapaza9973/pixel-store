@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Panel de control · ' . now()->translatedFormat('l, d \\d\\e F \\d\\e Y'))

@section('content')
<div class="space-y-6">

    {{-- Bienvenida --}}
    <div class="bg-gradient-to-r from-blue-600/10 via-blue-500/5 to-transparent border border-blue-500/20 rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-white">
                    ¡Bienvenido, {{ auth()->user()->name }}!
                </h2>
                <p class="text-slate-400 mt-1">
                    Has iniciado sesión como <span class="text-blue-400 font-medium">{{ auth()->user()->getRoleNames()->first() }}</span>.
                </p>
            </div>
            <img src="{{ asset('images/logo/pixel-icon-sm.png') }}"
                 alt="Pixel Store"
                 class="w-16 h-16 rounded opacity-80">
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Productos totales</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ number_format($productosTotales) }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $productosStockBajo }} en stock bajo</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Stock bajo</p>
                    <p class="text-2xl font-bold text-amber-400 mt-1">{{ number_format($productosStockBajo) }}</p>
                    <p class="text-xs text-slate-500 mt-1">Productos que requieren reposición</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Valor del inventario</p>
                    <p class="text-2xl font-bold text-emerald-400 mt-1">Bs {{ number_format($valorInventarioBs, 2) }}</p>
                    <p class="text-xs text-slate-500 mt-1">Bs sumando precio × stock</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Categorías</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ number_format($categoriasTotales) }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $marcasTotales }} marcas registradas</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
            </div>
        </x-card>
    </div>

    {{-- Gráfico + resumen por ubicación --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <x-card title="Stock por categoría">
            @if ($stockPorCategoria->isEmpty())
                <p class="text-sm text-slate-500 text-center py-12">Aún no hay stock registrado por categoría.</p>
            @else
                <div class="h-72">
                    <canvas id="stockChart"></canvas>
                </div>
            @endif
        </x-card>

        <x-card title="Resumen por ubicación">
            @forelse ($stockPorUbicacion as $item)
                <div class="flex items-center justify-between py-3 border-b border-slate-800 last:border-0">
                    <div>
                        <p class="text-sm text-white">{{ $item->ubicacion->nombre ?? '—' }}</p>
                        <span class="inline-flex mt-1 px-2 py-0.5 text-[10px] font-semibold uppercase rounded-full bg-slate-700/50 text-slate-400">
                            {{ $item->ubicacion->tipo ?? '—' }}
                        </span>
                    </div>
                    <p class="text-xl font-bold text-white">{{ number_format((int) $item->total_stock) }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500 text-center py-12">No hay ubicaciones con stock registrado.</p>
            @endforelse
        </x-card>
    </div>

    {{-- Productos críticos --}}
    <x-card title="Productos críticos" subtitle="Stock igual o bajo el umbral">
        @if ($productosCriticos->isEmpty())
            <p class="text-sm text-slate-500 text-center py-6">Todo el inventario está saludable.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-3 py-3">Producto</th>
                            <th class="px-3 py-3">SKU</th>
                            <th class="px-3 py-3">Categoría</th>
                            <th class="px-3 py-3 text-center">Stock</th>
                            <th class="px-3 py-3 text-center">Umbral</th>
                            <th class="px-3 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach ($productosCriticos as $producto)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-3 py-3 text-white font-medium">{{ $producto->nombre }}</td>
                                <td class="px-3 py-3 text-slate-400">{{ $producto->sku ?? 'S/N' }}</td>
                                <td class="px-3 py-3 text-slate-300">{{ $producto->categoria->nombre ?? '—' }}</td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-500/10 text-red-400">
                                        {{ $producto->stock }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center text-slate-400">{{ $producto->umbral_alerta }}</td>
                                <td class="px-3 py-3 text-right">
                                    @can('editar productos')
                                        @if (Route::has('admin.productos.edit'))
                                            <a href="{{ route('admin.productos.edit', $producto) }}"
                                               class="text-xs text-blue-400 hover:text-blue-300 transition">Reponer</a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    {{-- Accesos rápidos --}}
    <x-card title="Accesos rápidos">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @can('crear productos')
                @if (Route::has('admin.productos.create'))
                    <a href="{{ route('admin.productos.create') }}"
                       class="flex items-center gap-3 p-3 bg-slate-800/50 hover:bg-slate-800 border border-slate-700 rounded-lg transition">
                        <div class="w-9 h-9 rounded bg-emerald-500/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <span class="text-sm text-slate-300">Nuevo producto</span>
                    </a>
                @endif
            @endcan

            @can('ver productos')
                @if (Route::has('admin.productos.index'))
                    <a href="{{ route('admin.productos.index') }}"
                       class="flex items-center gap-3 p-3 bg-slate-800/50 hover:bg-slate-800 border border-slate-700 rounded-lg transition">
                        <div class="w-9 h-9 rounded bg-blue-500/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <span class="text-sm text-slate-300">Ver listado</span>
                    </a>
                @endif
            @endcan

            @can('ver categorias')
                @if (Route::has('admin.categorias.index'))
                    <a href="{{ route('admin.categorias.index') }}"
                       class="flex items-center gap-3 p-3 bg-slate-800/50 hover:bg-slate-800 border border-slate-700 rounded-lg transition">
                        <div class="w-9 h-9 rounded bg-purple-500/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-slate-300">Categorías</span>
                    </a>
                @endif
            @endcan
        </div>
    </x-card>

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
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { color: '#94a3b8' }, grid: { color: '#1e293b' } },
                    x: { ticks: { color: '#94a3b8' }, grid: { display: false } }
                }
            }
        });
    }
</script>
@endsection
