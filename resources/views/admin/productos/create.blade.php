@extends('layouts.app')

@section('title', 'Nuevo producto')
@section('subtitle', 'Registrar un nuevo producto en el catálogo')

@section('content')
<div class="max-w-4xl"
     x-data="productoForm({
         atributosPorCategoria: @js($atributosPorCategoria),
         seleccionada: @js(old('categoria_id')),
         valores: @js(old('atributos', []))
     })">
    <form method="POST" action="{{ route('admin.productos.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Datos generales --}}
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 space-y-5">
            <h3 class="text-sm font-semibold text-on-surface-variant uppercase tracking-wider">Información básica</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="nombre" class="block text-sm font-medium text-on-surface-variant mb-1.5">
                        Nombre <span class="text-error">*</span>
                    </label>
                    <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}"
                           class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                    @error('nombre') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="sku" class="block text-sm font-medium text-on-surface-variant mb-1.5">SKU</label>
                    <input type="text" name="sku" id="sku" value="{{ old('sku') }}"
                           class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                    @error('sku') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="categoria_id" class="block text-sm font-medium text-on-surface-variant mb-1.5">
                        Categoría <span class="text-error">*</span>
                    </label>
                    <select name="categoria_id" id="categoria_id" x-model="categoriaSeleccionada"
                            class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                        <option value="">Seleccione...</option>
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ old('categoria_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('categoria_id') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="marca_id" class="block text-sm font-medium text-on-surface-variant mb-1.5">Marca</label>
                    <select name="marca_id" id="marca_id"
                            class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                        <option value="">Sin marca</option>
                        @foreach ($marcas as $marca)
                            <option value="{{ $marca->id }}" {{ old('marca_id') == $marca->id ? 'selected' : '' }}>
                                {{ $marca->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('marca_id') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="codigo_barras" class="block text-sm font-medium text-on-surface-variant mb-1.5">Código de barras</label>
                    <input type="text" name="codigo_barras" id="codigo_barras" value="{{ old('codigo_barras') }}"
                           class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                </div>
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium text-on-surface-variant mb-1.5">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="3"
                          class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">{{ old('descripcion') }}</textarea>
            </div>

            <div>
                <label for="imagen" class="block text-sm font-medium text-on-surface-variant mb-1.5">Imagen principal</label>
                <input type="file" name="imagen" id="imagen" accept="image/*"
                       class="w-full text-sm text-on-surface-variant file:mr-3 file:py-2 file:px-3 file:rounded-obsidian-lg file:border-0 file:bg-surface-container-low file:text-on-surface hover:file:bg-surface-container">
                @error('imagen') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Precios y stock --}}
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 space-y-5">
            <h3 class="text-sm font-semibold text-on-surface-variant uppercase tracking-wider">Precios y stock</h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="precio_unitario" class="block text-sm font-medium text-on-surface-variant mb-1.5">
                        Precio <span class="text-error">*</span>
                    </label>
                    <input type="number" step="0.01" name="precio_unitario" id="precio_unitario" value="{{ old('precio_unitario') }}"
                           class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                    @error('precio_unitario') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="costo" class="block text-sm font-medium text-on-surface-variant mb-1.5">Costo</label>
                    <input type="number" step="0.01" name="costo" id="costo" value="{{ old('costo') }}"
                           class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                    @error('costo') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="stock" class="block text-sm font-medium text-on-surface-variant mb-1.5">
                        Stock <span class="text-error">*</span>
                    </label>
                    <input type="number" name="stock" id="stock" value="{{ old('stock', 0) }}"
                           class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                    @error('stock') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="umbral_alerta" class="block text-sm font-medium text-on-surface-variant mb-1.5">
                        Umbral alerta <span class="text-error">*</span>
                    </label>
                    <input type="number" name="umbral_alerta" id="umbral_alerta" value="{{ old('umbral_alerta', 5) }}"
                           class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                    @error('umbral_alerta') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="ubicacion_id" class="block text-sm font-medium text-on-surface-variant mb-1.5">
                    Ubicación del stock inicial
                </label>
                <select name="ubicacion_id" id="ubicacion_id"
                        class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                    <option value="">Usar ubicación primaria (Tienda)</option>
                    @foreach (['tienda' => 'Tienda', 'deposito' => 'Depósito'] as $tipo => $etiqueta)
                        @php $grupo = $ubicaciones->where('tipo', $tipo); @endphp
                        @if ($grupo->isNotEmpty())
                            <optgroup label="{{ $etiqueta }}">
                                @foreach ($grupo as $ubicacion)
                                    <option value="{{ $ubicacion->id }}" @selected(old('ubicacion_id') == $ubicacion->id)>
                                        {{ $ubicacion->nombre_completo }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
                @error('ubicacion_id') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-6">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="maneja_numero_serie" value="1" {{ old('maneja_numero_serie') ? 'checked' : '' }}
                           class="rounded bg-surface-container-low border-outline-variant/30 text-primary-container focus:ring-primary-container/40">
                    <span class="text-sm text-on-surface-variant">Maneja número de serie</span>
                </label>

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="visible_catalogo" value="1" {{ old('visible_catalogo', true) ? 'checked' : '' }}
                           class="rounded bg-surface-container-low border-outline-variant/30 text-primary-container focus:ring-primary-container/40">
                    <span class="text-sm text-on-surface-variant">Visible en catálogo</span>
                </label>

                <div x-data="{ oculto: {{ old('ocultar_si_sin_stock') ? 'true' : 'false' }} }" class="flex flex-col">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="ocultar_si_sin_stock" value="1" x-model="oculto"
                               class="rounded bg-surface-container-low border-outline-variant/30 text-primary-container focus:ring-primary-container/40">
                        <span class="text-sm text-on-surface-variant">Ocultar si no hay stock</span>
                    </label>
                    <span x-show="oculto" x-cloak class="text-xs text-outline">Se ocultará del catálogo cuando el stock llegue a 0.</span>
                </div>
            </div>
        </div>

        @include('admin.productos.partials.atributos')

        {{-- Botones --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-4 py-2 bg-primary-container hover:bg-primary-container/90 text-on-primary-container text-sm font-medium rounded-obsidian-lg transition">
                Guardar producto
            </button>
            <a href="{{ route('admin.productos.index') }}"
               class="px-4 py-2 border border-outline-variant/30 bg-surface-container-low text-on-surface-variant text-sm font-medium rounded-obsidian-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
