@extends('layouts.app')

@section('title', 'Editar Producto')
@section('subtitle', 'Modificar información y atributos del producto')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-white tracking-tight">Editar Producto: {{ $producto->nombre }}</h2>
        <a href="{{ route('inventario.index') }}" 
           class="text-xs text-slate-400 hover:text-white transition-colors">
            &larr; Volver al inventario
        </a>
    </div>

    {{-- Formulario Principal --}}
    <form action="{{ route('inventario.update', $producto->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Datos Generales --}}
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-xl space-y-6">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider border-b border-slate-800 pb-3">
                Información Básica
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                {{-- Nombre --}}
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nombre *</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre) }}" required
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                    @error('nombre') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Categoría --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Categoría *</label>
                    <select name="categoria_id" id="categoria_id" required
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($categorias as$cat)
                            <option value="{{ $cat->id }}" {{ old('categoria_id', $producto->categoria_id) ==$cat->id ? 'selected' : '' }}>
                                {{ $cat->nombre }} {{ $cat->tipo ? '('.$cat->tipo.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('categoria_id') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Marca --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Marca</label>
                    <select name="marca_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="">Sin Marca</option>
                        @foreach($marcas as$marca)
                            <option value="{{ $marca->id }}" {{ old('marca_id', $producto->marca_id) ==$marca->id ? 'selected' : '' }}>
                                {{ $marca->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Precio Unitario --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Precio Unitario ($) *</label>
                    <input type="number" step="0.01" name="precio_unitario" value="{{ old('precio_unitario', $producto->precio_unitario) }}" required
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- Costo --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Costo ($)</label>
                    <input type="number" step="0.01" name="costos" value="{{ old('costos', $producto->costos) }}"
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- Stock --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Stock *</label>
                    <input type="number" name="stock" value="{{ old('stock', $producto->stock) }}" required
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- Umbral Alerta --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Umbral Alerta</label>
                    <input type="number" name="umbral_alerta" value="{{ old('umbral_alerta', $producto->umbral_alerta) }}"
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- SKU --}}
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $producto->sku) }}"
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- Código de Barras --}}
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Código de Barras</label>
                    <input type="text" name="codigo_barras" value="{{ old('codigo_barras', $producto->codigo_barras) }}"
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- Descripción --}}
                <div class="md:col-span-12">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Descripción</label>
                    <textarea name="descripcion" rows="3"
                              class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">{{ old('descripcion', $producto->descripcion) }}</textarea>
                </div>

                {{-- Checkboxes --}}
                <div class="md:col-span-12 flex flex-wrap gap-6 pt-2">
                    <label class="inline-flex items-center cursor-pointer gap-2">
                        <input type="checkbox" name="maneja_numero_serie" value="1" {{ old('maneja_numero_serie', $producto->maneja_numero_serie) ? 'checked' : '' }}
                               class="rounded bg-slate-950 border-slate-800 text-blue-600 focus:ring-0">
                        <span class="text-sm text-slate-300">Maneja Número de Serie</span>
                    </label>

                    <label class="inline-flex items-center cursor-pointer gap-2">
                        <input type="checkbox" name="visible_catalogo" value="1" {{ old('visible_catalogo', $producto->visible_catalogo) ? 'checked' : '' }}
                               class="rounded bg-slate-950 border-slate-800 text-blue-600 focus:ring-0">
                        <span class="text-sm text-slate-300">Visible en Catálogo</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Atributos Técnicos Dinámicos --}}
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-xl space-y-4">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider border-b border-slate-800 pb-3">
                Atributos Técnicos
            </h3>

            <div id="contenedor-atributos" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <p class="text-xs text-slate-500 md:col-span-3">Cargando atributos del producto...</p>
            </div>
        </div>

        {{-- Botón de Acción --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('inventario.index') }}" 
               class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-lg transition-colors">
                Cancelar
            </a>
            <button type="submit" 
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-lg transition-colors shadow-lg shadow-blue-600/20">
                Actualizar Producto
            </button>
        </div>
    </form>
</div>

<script>
// Objeto con los valores guardados actualmente en la base de datos
const valoresAtributos = @json($producto->atributosValores ? $producto->atributosValores->pluck('valor', 'atributo_id') : {});

function cargarAtributos(categoriaId) {
    const contenedor = document.getElementById('contenedor-atributos');
    contenedor.innerHTML = '';

    if (!categoriaId) {
        contenedor.innerHTML = '<p class="text-xs text-slate-500 md:col-span-3">Seleccione una categoría para cargar sus atributos específicos.</p>';
        return;
    }

    fetch(`/inventario/atributos-categoria/${categoriaId}`)
        .then(res => res.json())
        .then(atributos => {
            if (atributos.length === 0) {
                contenedor.innerHTML = '<p class="text-xs text-slate-500 md:col-span-3">Esta categoría no posee atributos dinámicos configurados.</p>';
                return;
            }

            atributos.forEach(attr => {
                const valorActual = valoresAtributos[attr.id] !== undefined ? valoresAtributos[attr.id] : '';

                let html = `<div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        ${attr.nombre} ${attr.unidad ? '<span class="text-slate-500">(' + attr.unidad + ')</span>' : ''}
                    </label>`;

                if (attr.tipo_dato === 'string') {
                    html += `<input type="text" name="atributos[${attr.id}]" value="${valorActual}" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">`;
                } else if (attr.tipo_dato === 'integer') {
                    html += `<input type="number" step="1" name="atributos[${attr.id}]" value="${valorActual}" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">`;
                } else if (attr.tipo_dato === 'decimal') {
                    html += `<input type="number" step="0.0001" name="atributos[${attr.id}]" value="${valorActual}" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">`;
                } else if (attr.tipo_dato === 'boolean') {
                    html += `<select name="atributos[${attr.id}]" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="">N/A</option>
                        <option value="1" ${valorActual == '1' ? 'selected' : ''}>Sí</option>
                        <option value="0" ${valorActual == '0' ? 'selected' : ''}>No</option>
                    </select>`;
                } else if (attr.tipo_dato === 'enum') {
                    html += `<select name="atributos[${attr.id}]" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="">Seleccione...</option>`;
                    attr.valores_predefinidos.forEach(val => {
                        html += `<option value="${val.id}" ${valorActual == val.id ? 'selected' : ''}>${val.valor}</option>`;
                    });
                    html += `</select>`;
                }

                html += `</div>`;
                contenedor.insertAdjacentHTML('beforeend', html);
            });
        });
}

// Carga inicial al abrir la pantalla de edición
document.addEventListener('DOMContentLoaded', function () {
    const selectCat = document.getElementById('categoria_id');
    if (selectCat.value) {
        cargarAtributos(selectCat.value);
    }

    // Escuchar cambios de categoría
    selectCat.addEventListener('change', function () {
        cargarAtributos(this.value);
    });
});
</script>
@endsection