@extends('layouts.app')

@section('title', 'Inventario de Productos')
@section('subtitle', 'Gestión general del catálogo y existencias')

@section('content')
<div class="space-y-6">

    {{-- Encabezado y Botón Agregar --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Inventario de Productos</h2>
            <p class="text-xs text-slate-400">Total de registros gestionados en el sistema</p>
        </div>
        <a href="{{ route('inventario.create') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-lg transition-colors shadow-lg shadow-blue-600/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            + Agregar Producto
        </a>
    </div>

    {{-- Mensaje de Éxito --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabla de Productos --}}
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/50 text-slate-400 text-xs uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-3.5 font-semibold">SKU</th>
                        <th class="px-6 py-3.5 font-semibold">Nombre</th>
                        <th class="px-6 py-3.5 font-semibold">Categoría</th>
                        <th class="px-6 py-3.5 font-semibold">Marca</th>
                        <th class="px-6 py-3.5 font-semibold">Precio Unitario</th>
                        <th class="px-6 py-3.5 font-semibold">Costo</th>
                        <th class="px-6 py-3.5 font-semibold">Stock</th>
                        <th class="px-6 py-3.5 font-semibold text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($productos as $prod)
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-slate-400">
                                <code>{{ $prod->sku ?? 'S/N' }}</code>
                            </td>
                            <td class="px-6 py-4 font-medium text-white">
                                {{ $prod->nombre }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $prod->categoria->nombre ?? 'Sin Categoría' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-400">
                                {{ $prod->marca->nombre ?? 'Sin Marca' }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-emerald-400">
                                ${{ number_format($prod->precio_unitario, 2) }}
                            </td>
                            <td class="px-6 py-4 text-slate-400">
                                ${{ number_format($prod->costos, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                @if($prod->stock <= $prod->umbral_alerta)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                        {{ $prod->stock }} unids.
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $prod->stock }} unids.
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Botón Ver Detalle --}}
                                    <a href="{{ route('inventario.show', $prod->id) }}" 
                                       class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-lg transition-colors border border-slate-700"
                                       title="Ver Detalle">
                                        Ver
                                    </a>

                                    {{-- Botón Editar --}}
                                    <a href="{{ route('inventario.edit', $prod->id) }}" 
                                       class="px-3 py-1 bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 text-xs font-medium rounded-lg transition-colors border border-blue-500/30"
                                       title="Editar Producto">
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-500">
                                No hay productos registrados en la base de datos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginación --}}
    <div class="pt-2">
        {{ $productos->links() }}
    </div>

</div>
@endsection