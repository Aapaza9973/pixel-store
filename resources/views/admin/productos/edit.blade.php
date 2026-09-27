@extends('layouts.app')

@section('title', 'Editar producto')
@section('subtitle', $producto->nombre)

@section('content')
<div class="max-w-4xl"
     x-data="productoForm({
         atributosPorCategoria: @js($atributosPorCategoria),
         seleccionada: @js(old('categoria_id', $producto->categoria_id)),
         valores: @js(old('atributos', $valoresActuales))
     })">
    <form method="POST" action="{{ route('admin.productos.update', $producto) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Datos generales --}}
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-5">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Información básica</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label for="nombre" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Nombre <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $producto->nombre) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('nombre') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="sku" class="block text-sm font-medium text-slate-300 mb-1.5">SKU</label>
                    <input type="text" name="sku" id="sku" value="{{ old('sku', $producto->sku) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('sku') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="categoria_id" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Categoría <span class="text-red-400">*</span>
                    </label>
                    <select name="categoria_id" id="categoria_id" x-model="categoriaSeleccionada"
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        @foreach ($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ old('categoria_id', $producto->categoria_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('categoria_id') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="marca_id" class="block text-sm font-medium text-slate-300 mb-1.5">Marca</label>
                    <select name="marca_id" id="marca_id"
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <option value="">Sin marca</option>
                        @foreach ($marcas as $marca)
                            <option value="{{ $marca->id }}" {{ old('marca_id', $producto->marca_id) == $marca->id ? 'selected' : '' }}>
                                {{ $marca->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('marca_id') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="codigo_barras" class="block text-sm font-medium text-slate-300 mb-1.5">Código de barras</label>
                    <input type="text" name="codigo_barras" id="codigo_barras" value="{{ old('codigo_barras', $producto->codigo_barras) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium text-slate-300 mb-1.5">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="3"
                          class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">{{ old('descripcion', $producto->descripcion) }}</textarea>
            </div>

            <div class="space-y-3">
                <label for="imagen" class="block text-sm font-medium text-slate-300">Imagen principal</label>

                @if ($producto->imagen_principal)
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('storage/'.$producto->imagen_principal) }}"
                             alt="{{ $producto->nombre }}"
                             class="w-20 h-20 object-cover rounded-lg border border-slate-800">
                        <span class="text-xs text-slate-500">Imagen actual</span>
                    </div>
                @endif

                <input type="file" name="imagen" id="imagen" accept="image/*"
                       class="w-full text-sm text-slate-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700">
                @error('imagen') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Precios y stock --}}
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-5">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Precios y stock</h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="precio_unitario" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Precio <span class="text-red-400">*</span>
                    </label>
                    <input type="number" step="0.01" name="precio_unitario" id="precio_unitario" value="{{ old('precio_unitario', $producto->precio_unitario) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('precio_unitario') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="costo" class="block text-sm font-medium text-slate-300 mb-1.5">Costo</label>
                    <input type="number" step="0.01" name="costo" id="costo" value="{{ old('costo', $producto->costo) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('costo') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="stock" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Stock <span class="text-red-400">*</span>
                    </label>
                    <input type="number" name="stock" id="stock" value="{{ old('stock', $producto->stock) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('stock') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="umbral_alerta" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Umbral alerta <span class="text-red-400">*</span>
                    </label>
                    <input type="number" name="umbral_alerta" id="umbral_alerta" value="{{ old('umbral_alerta', $producto->umbral_alerta) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('umbral_alerta') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="ubicacion_id" class="block text-sm font-medium text-slate-300 mb-1.5">
                    Ubicación para el ajuste de stock
                </label>
                <select name="ubicacion_id" id="ubicacion_id"
                        class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @foreach ($ubicaciones as $ubicacion)
                        <option value="{{ $ubicacion->id }}" @selected(old('ubicacion_id', $ubicacionPorDefecto) == $ubicacion->id)>
                            {{ $ubicacion->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('ubicacion_id') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-6">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="maneja_numero_serie" value="1" {{ old('maneja_numero_serie', $producto->maneja_numero_serie) ? 'checked' : '' }}
                           class="rounded bg-slate-800 border-slate-700 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-slate-300">Maneja número de serie</span>
                </label>

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="visible_catalogo" value="1" {{ old('visible_catalogo', $producto->visible_catalogo) ? 'checked' : '' }}
                           class="rounded bg-slate-800 border-slate-700 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-slate-300">Visible en catálogo</span>
                </label>
            </div>
        </div>

        @include('admin.productos.partials.atributos')

        {{-- Botones --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                Actualizar producto
            </button>
            <a href="{{ route('admin.productos.index') }}"
               class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
