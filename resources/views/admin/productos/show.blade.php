@extends('layouts.app')

@section('title', $producto->nombre)
@section('subtitle', 'Detalle del producto')

@section('content')
<div class="max-w-4xl space-y-6">

    {{-- Encabezado con acciones --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-slate-500">SKU: <code class="text-slate-300">{{ $producto->sku ?? 'S/N' }}</code></p>
        </div>
        <div class="flex items-center gap-2">
            @can('editar productos')
                <a href="{{ route('admin.productos.edit', $producto) }}"
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    Editar
                </a>
            @endcan
            <a href="{{ route('admin.productos.index') }}"
               class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                Volver
            </a>
        </div>
    </div>

    {{-- Info general --}}
    <x-card title="Información general">
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Categoría</dt>
                <dd class="text-white">{{ $producto->categoria->nombre ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Marca</dt>
                <dd class="text-white">{{ $producto->marca->nombre ?? 'Sin marca' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Código de barras</dt>
                <dd class="text-white">{{ $producto->codigo_barras ?? '—' }}</dd>
            </div>
            <div class="md:col-span-3">
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Descripción</dt>
                <dd class="text-slate-300">{{ $producto->descripcion ?? 'Sin descripción' }}</dd>
            </div>
        </dl>
    </x-card>

    {{-- Precios y stock --}}
    <x-card title="Precios y stock">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Precio</dt>
                <dd class="text-emerald-400 font-semibold text-lg">Bs {{ number_format($producto->precio_unitario, 2) }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Costo</dt>
                <dd class="text-white">Bs {{ number_format($producto->costo ?? 0, 2) }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Stock</dt>
                <dd class="{{ $producto->tieneStockBajo() ? 'text-red-400' : 'text-emerald-400' }} font-semibold text-lg">
                    {{ $producto->stock }} unid.
                </dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Umbral de alerta</dt>
                <dd class="text-slate-300">{{ $producto->umbral_alerta }} unid.</dd>
            </div>
        </div>
    </x-card>

    {{-- Atributos técnicos --}}
    <x-card title="Atributos técnicos" subtitle="Especificaciones del producto (EAV)">
        @if ($producto->atributos->isEmpty())
            <p class="text-sm text-slate-500 text-center py-4">Sin atributos especificados.</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach ($producto->atributos as $item)
                    <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg border border-slate-700/50">
                        <span class="text-xs text-slate-400 uppercase tracking-wider">{{ $item->atributo->nombre }}</span>
                        <span class="text-sm text-white font-medium">
                            {{ $item->valor_legible }}
                            @if ($item->atributo->unidad)
                                <span class="text-slate-500 text-xs">{{ $item->atributo->unidad }}</span>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

</div>
@endsection
