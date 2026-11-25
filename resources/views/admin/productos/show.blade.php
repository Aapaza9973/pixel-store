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
            <p class="text-xs text-outline">SKU: <code class="text-on-surface-variant">{{ $producto->sku ?? 'S/N' }}</code></p>
        </div>
        <div class="flex items-center gap-2">
            @can('editar productos')
                <a href="{{ route('admin.productos.edit', $producto) }}"
                   class="px-4 py-2 bg-primary-container hover:bg-primary-container/90 text-on-primary-container text-sm font-medium rounded-obsidian-lg transition">
                    Editar
                </a>
            @endcan
            <a href="{{ route('admin.productos.index') }}"
               class="px-4 py-2 border border-outline-variant/30 bg-surface-container-low text-on-surface-variant text-sm font-medium rounded-obsidian-lg transition">
                Volver
            </a>
        </div>
    </div>

    {{-- Info general --}}
    <x-card title="Información general">
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <dt class="text-outline text-xs uppercase tracking-wider mb-1">Categoría</dt>
                <dd class="text-on-surface">{{ $producto->categoria->nombre ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-outline text-xs uppercase tracking-wider mb-1">Marca</dt>
                <dd class="text-on-surface">{{ $producto->marca->nombre ?? 'Sin marca' }}</dd>
            </div>
            <div>
                <dt class="text-outline text-xs uppercase tracking-wider mb-1">Código de barras</dt>
                <dd class="text-on-surface">{{ $producto->codigo_barras ?? '—' }}</dd>
            </div>
            <div class="md:col-span-3">
                <dt class="text-outline text-xs uppercase tracking-wider mb-1">Descripción</dt>
                <dd class="text-on-surface-variant">{{ $producto->descripcion ?? 'Sin descripción' }}</dd>
            </div>
        </dl>
    </x-card>

    {{-- Precios y stock --}}
    <x-card title="Precios y stock">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <dt class="text-outline text-xs uppercase tracking-wider mb-1">Precio</dt>
                <dd class="text-emerald-400 font-semibold text-lg">{{ \App\Support\Dinero::formatear($producto->precio_unitario) }}</dd>
            </div>
            <div>
                <dt class="text-outline text-xs uppercase tracking-wider mb-1">Costo</dt>
                <dd class="text-on-surface">{{ \App\Support\Dinero::formatear($producto->costo ?? 0) }}</dd>
            </div>
            <div>
                <dt class="text-outline text-xs uppercase tracking-wider mb-1">Stock</dt>
                <dd class="{{ $producto->tieneStockBajo() ? 'text-error' : 'text-emerald-400' }} font-semibold text-lg">
                    {{ $producto->stock }} unid.
                </dd>
            </div>
            <div>
                <dt class="text-outline text-xs uppercase tracking-wider mb-1">Umbral de alerta</dt>
                <dd class="text-on-surface-variant">{{ $producto->umbral_alerta }} unid.</dd>
            </div>
            <div>
                <dt class="text-outline text-xs uppercase tracking-wider mb-1">Catálogo</dt>
                <dd class="text-on-surface-variant">
                    {{ $producto->visible_catalogo ? 'Visible' : 'Oculto' }}
                    @if ($producto->ocultar_si_sin_stock)
                        · se oculta sin stock
                    @endif
                </dd>
            </div>
        </div>
    </x-card>

    {{-- Stock por ubicación --}}
    <x-card title="Stock por ubicación" subtitle="Desglose físico del inventario">
        <x-slot:actions>
            @can('editar productos')
                @if ($ubicacionesConStock->count() >= 2)
                    <button type="button" @click="transferirAbierto = true"
                            class="px-4 py-2 border border-outline-variant/30 bg-surface-container-low text-on-surface text-sm font-medium rounded-obsidian-lg transition">
                        Transferir stock
                    </button>
                @endif
            @endcan
        </x-slot:actions>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-outline-variant/15 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-outline">
                        <th class="px-3 py-3">Ubicación</th>
                        <th class="px-3 py-3">Tipo</th>
                        <th class="px-3 py-3 text-right">Cantidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15">
                    @forelse ($stockPorUbicacion as $item)
                        <tr class="hover:bg-primary-container/[0.04] transition">
                            <td class="px-3 py-3 text-on-surface">{{ $item->ubicacion?->nombre_completo ?? 'Ubicación eliminada' }}</td>
                            <td class="px-3 py-3">
                                @if ($item->ubicacion?->tipo === 'tienda')
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border border-secondary/25 bg-secondary/10 text-secondary">Tienda</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border border-outline-variant/30 bg-surface-container-low text-on-surface-variant">Depósito</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right text-on-surface font-medium">{{ $item->cantidad }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-8 text-center text-sm text-outline">
                                Sin stock asignado a ubicaciones.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t border-outline-variant/15">
                        <td colspan="2" class="px-3 py-3 text-xs uppercase tracking-wider text-outline">Total por ubicación</td>
                        <td class="px-3 py-3 text-right font-bold text-on-surface">{{ $totalUbicaciones }}</td>
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
            <p class="text-sm text-outline text-center py-4">Sin atributos especificados.</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach ($producto->atributos as $item)
                    <div class="flex items-center justify-between p-3 bg-surface-container-low/50 rounded-obsidian-lg border border-outline-variant/30/50">
                        <span class="text-xs text-on-surface-variant uppercase tracking-wider">{{ $item->atributo->nombre }}</span>
                        <span class="text-sm text-on-surface font-medium">
                            {{ $item->valor_legible }}
                            @if ($item->atributo->unidad)
                                <span class="text-outline text-xs">{{ $item->atributo->unidad }}</span>
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

            <div class="relative w-full max-w-lg rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 id="transferir-stock-title" class="text-sm font-semibold text-on-surface">Transferir stock</h3>
                    <button type="button" @click="transferirAbierto = false"
                            class="text-on-surface-variant hover:text-on-surface transition" aria-label="Cerrar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.productos.transferir-stock', $producto) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="ubicacion_origen_id" class="block text-sm font-medium text-on-surface-variant mb-1.5">
                            Origen (ubicaciones con stock)
                        </label>
                        <select name="ubicacion_origen_id" id="ubicacion_origen_id" x-model="origen"
                                class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                            @foreach ($ubicacionesConStock as $item)
                                <option value="{{ $item->ubicacion_id }}">
                                    {{ $item->ubicacion?->nombre_completo ?? 'Ubicación eliminada' }} ({{ $item->cantidad }})
                                </option>
                            @endforeach
                        </select>
                        @error('ubicacion_origen_id') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="ubicacion_destino_id" class="block text-sm font-medium text-on-surface-variant mb-1.5">
                            Destino
                        </label>
                        <select name="ubicacion_destino_id" id="ubicacion_destino_id"
                                class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                            <template x-for="ubicacion in destinos" :key="ubicacion.id">
                                <option :value="ubicacion.id" x-text="ubicacion.nombre"></option>
                            </template>
                        </select>
                        @error('ubicacion_destino_id') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="cantidad" class="block text-sm font-medium text-on-surface-variant mb-1.5">
                            Cantidad <span class="text-outline text-xs">(máx. <span x-text="maxCantidad"></span>)</span>
                        </label>
                        <input type="number" name="cantidad" id="cantidad" min="1" :max="maxCantidad" value="1"
                               class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                        @error('cantidad') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <button type="button" @click="transferirAbierto = false"
                                class="px-4 py-2 border border-outline-variant/30 bg-surface-container-low text-on-surface-variant text-sm font-medium rounded-obsidian-lg transition">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-primary-container hover:bg-primary-container/90 text-on-primary-container text-sm font-medium rounded-obsidian-lg transition">
                            Transferir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    {{-- Sprint 2: galería de imágenes --}}
    <x-card title="Imágenes" subtitle="Galería del producto">
        @if ($producto->imagenesGaleria->isNotEmpty())
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach ($producto->imagenesGaleria as $imagen)
                    <div class="relative rounded-obsidian-lg overflow-hidden border border-outline-variant/15 bg-surface-container-low">
                        <img src="{{ asset('storage/'.$imagen->path) }}"
                             alt="{{ $imagen->alt ?? $producto->nombre }}"
                             class="aspect-square w-full object-cover">
                        @if ($imagen->es_principal)
                            <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider rounded bg-primary-container text-on-surface">
                                Principal
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-outline">Este producto todavía no tiene imágenes cargadas.</p>
        @endif

        @can('editar productos')
            <a href="{{ route('admin.productos.edit', $producto) }}"
               class="inline-flex mt-4 text-sm text-secondary hover:text-on-surface font-medium transition">
                Administrar imágenes
            </a>
        @endcan
    </x-card>

</div>
@endsection
