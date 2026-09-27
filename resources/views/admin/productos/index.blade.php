@extends('layouts.app')

@section('title', 'Productos')
@section('subtitle', 'Catálogo e inventario de productos')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-white tracking-tight">Listado de productos</h2>

        @can('crear productos')
            <a href="{{ route('admin.productos.create') }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                Nuevo producto
            </a>
        @endcan
    </div>

    <!-- Filtros Tarea 11 -->

    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-3 py-3">Producto</th>
                        <th class="px-3 py-3">SKU</th>
                        <th class="px-3 py-3">Categoría</th>
                        <th class="px-3 py-3">Marca</th>
                        <th class="px-3 py-3 text-right">Precio</th>
                        <th class="px-3 py-3 text-center">Stock</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($productos as $producto)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($producto->imagen_principal)
                                        <img src="{{ asset('storage/'.$producto->imagen_principal) }}"
                                             alt="{{ $producto->nombre }}"
                                             class="w-10 h-10 object-cover rounded-lg border border-slate-800">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="text-white font-medium truncate">{{ $producto->nombre }}</p>
                                        @if (! $producto->visible_catalogo)
                                            <span class="inline-flex mt-1 px-2 py-0.5 text-[10px] font-semibold uppercase rounded-full bg-slate-700/50 text-slate-400">
                                                Oculto
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-slate-400">{{ $producto->sku ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-300">{{ $producto->categoria->nombre ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-300">{{ $producto->marca->nombre ?? 'Sin marca' }}</td>
                            <td class="px-3 py-3 text-right text-emerald-400 font-medium">
                                Bs {{ number_format($producto->precio_unitario, 2) }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($producto->tieneStockBajo())
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-500/10 text-red-400">
                                        {{ $producto->stock }}
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400">
                                        {{ $producto->stock }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    @can('ver productos')
                                        <a href="{{ route('admin.productos.show', $producto) }}"
                                           class="text-xs text-slate-400 hover:text-white transition">Ver</a>
                                    @endcan

                                    @can('editar productos')
                                        <a href="{{ route('admin.productos.edit', $producto) }}"
                                           class="text-xs text-blue-400 hover:text-blue-300 transition">Editar</a>
                                    @endcan

                                    @can('eliminar productos')
                                        <form method="POST" action="{{ route('admin.productos.destroy', $producto) }}"
                                              onsubmit="return confirm('¿Está seguro de eliminar este producto?')">
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
                            <td colspan="7" class="px-3 py-10 text-center text-sm text-slate-500">
                                No hay productos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $productos->links() }}
        </div>
    </x-card>

</div>
@endsection
