@extends('layouts.app')

@section('title', $producto->nombre)
@section('subtitle', 'Detalle del producto')

@section('content')
@php
    $stockPorUbicacion = $producto->stockUbicaciones->sortByDesc('cantidad');
    $totalUbicaciones = (int) $stockPorUbicacion->sum('cantidad');
    $ubicacionesConStock = $stockPorUbicacion->where('cantidad', '>', 0);

    $ubicacionesConStockJs = $ubicacionesConStock->map(fn ($su) => [
        'id' => $su->ubicacion_id,
        'nombre' => $su->ubicacion?->nombre_completo ?? 'Ubicación eliminada',
        'cantidad' => (int) $su->cantidad,
    ])->values()->all();

    $ubicacionesActivasJs = collect($ubicaciones)->map(fn ($u) => [
        'id' => $u->id,
        'nombre' => $u->nombre_completo,
        'tipo' => $u->tipo,
    ])->values()->all();
@endphp

<div class="max-w-4xl space-y-6"
     x-data="{
         transferirAbierto: false,
         origen: @js((string) ($ubicacionesConStock->first()?->ubicacion_id ?? '')),
         conStock: @js($ubicacionesConStockJs),
         activas: @js($ubicacionesActivasJs),
         get maxCantidad() {
             const ubicacion = this.conStock.find(u => String(u.id) === String(this.origen));
             return ubicacion ? ubicacion.cantidad : 1;
         },
         get destinos() {
             return this.activas.filter(u => String(u.id) !== String(this.origen));
         }
     }">

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

    {{-- Stock por ubicación --}}
    <x-card title="Stock por ubicación" subtitle="Desglose físico del inventario">
        <x-slot:actions>
            @can('editar productos')
                @if ($ubicacionesConStock->count() >= 2)
                    <button type="button" @click="transferirAbierto = true"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-medium rounded-lg transition">
                        Transferir stock
                    </button>
                @endif
            @endcan
        </x-slot:actions>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-3 py-3">Ubicación</th>
                        <th class="px-3 py-3">Tipo</th>
                        <th class="px-3 py-3 text-right">Cantidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($stockPorUbicacion as $item)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-3 py-3 text-white">{{ $item->ubicacion?->nombre_completo ?? 'Ubicación eliminada' }}</td>
                            <td class="px-3 py-3">
                                @if ($item->ubicacion?->tipo === 'tienda')
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-500/10 text-blue-400">Tienda</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-violet-500/10 text-violet-400">Depósito</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right text-white font-medium">{{ $item->cantidad }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-8 text-center text-sm text-slate-500">
                                Sin stock asignado a ubicaciones.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t border-slate-800">
                        <td colspan="2" class="px-3 py-3 text-xs uppercase tracking-wider text-slate-500">Total por ubicación</td>
                        <td class="px-3 py-3 text-right font-bold text-white">{{ $totalUbicaciones }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($totalUbicaciones !== (int) $producto->stock)
            <p class="mt-2 text-xs text-amber-400">
                El total por ubicación ({{ $totalUbicaciones }}) no coincide con el stock del producto ({{ $producto->stock }}).
            </p>
        @endif
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

    {{-- Modal de transferencia --}}
    @can('editar productos')
        <div x-show="transferirAbierto" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="transferir-stock-title"
             @keydown.escape.window="transferirAbierto = false">
            <div class="absolute inset-0 bg-black/70" @click="transferirAbierto = false"></div>

            <div class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 id="transferir-stock-title" class="text-sm font-semibold text-white">Transferir stock</h3>
                    <button type="button" @click="transferirAbierto = false"
                            class="text-slate-400 hover:text-white transition" aria-label="Cerrar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.productos.transferir-stock', $producto) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="ubicacion_origen_id" class="block text-sm font-medium text-slate-300 mb-1.5">
                            Origen (ubicaciones con stock)
                        </label>
                        <select name="ubicacion_origen_id" id="ubicacion_origen_id" x-model="origen"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                            @foreach ($ubicacionesConStock as $item)
                                <option value="{{ $item->ubicacion_id }}">
                                    {{ $item->ubicacion?->nombre_completo ?? 'Ubicación eliminada' }} ({{ $item->cantidad }})
                                </option>
                            @endforeach
                        </select>
                        @error('ubicacion_origen_id') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="ubicacion_destino_id" class="block text-sm font-medium text-slate-300 mb-1.5">
                            Destino
                        </label>
                        <select name="ubicacion_destino_id" id="ubicacion_destino_id"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                            <template x-for="ubicacion in destinos" :key="ubicacion.id">
                                <option :value="ubicacion.id" x-text="ubicacion.nombre"></option>
                            </template>
                        </select>
                        @error('ubicacion_destino_id') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="cantidad" class="block text-sm font-medium text-slate-300 mb-1.5">
                            Cantidad <span class="text-slate-500 text-xs">(máx. <span x-text="maxCantidad"></span>)</span>
                        </label>
                        <input type="number" name="cantidad" id="cantidad" min="1" :max="maxCantidad" value="1"
                               class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        @error('cantidad') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <button type="button" @click="transferirAbierto = false"
                                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                            Transferir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

</div>
@endsection
