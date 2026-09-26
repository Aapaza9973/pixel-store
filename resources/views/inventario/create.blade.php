@extends('layouts.app')

@section('title', 'Crear Producto')
@section('subtitle', 'Registrar un nuevo elemento y sus atributos en el catálogo')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-white tracking-tight">Nuevo Producto</h2>
        <a href="{{ route('inventario.index') }}" 
           class="text-xs text-slate-400 hover:text-white transition-colors">
            &larr; Volver al inventario
        </a>
    </div>

    {{-- Formulario Principal --}}
    <form action="{{ route('inventario.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Datos Generales del Producto --}}
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-xl space-y-6">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider border-b border-slate-800 pb-3">
                Información Básica
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                {{-- Nombre --}}
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nombre *</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" required
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- Categoría (Corregido espacio en @foreach) --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Categoría *</label>
                    <select name="categoria_id" id="categoria_id" required
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ old('categoria_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->nombre }} {{ $cat->tipo ? '('.$cat->tipo.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Marca (Corregido espacio en @foreach) --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Marca</label>
                    <select name="marca_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="">Sin Marca</option>
                        @foreach($marcas as $marca)
                            <option value="{{ $marca->id }}" {{ old('marca_id') == $marca->id ? 'selected' : '' }}>
                                {{ $marca->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Precio Unitario --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Precio Unitario *</label>
                    <input type="number" step="0.01" name="precio_unitario" value="{{ old('precio_unitario') }}" required
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- Costo --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Costo</label>
                    <input type="number" step="0.01" name="costos" value="{{ old('costos') }}"
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- Stock --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Stock *</label>
                    <input type="number" name="stock" value="{{ old('stock', 0) }}" required
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- Umbral de Alerta --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Umbral Alerta</label>
                    <input type="number" name="umbral_alerta" value="{{ old('umbral_alerta', 5) }}"
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- SKU --}}
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku') }}"
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- Código de Barras --}}
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Código de Barras</label>
                    <input type="text" name="codigo_barras" value="{{ old('codigo_barras') }}"
                           class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>

                {{-- Descripción --}}
                <div class="md:col-span-12">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Descripción</label>
                    <textarea name="descripcion" rows="3"
                              class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">{{ old('descripcion') }}</textarea>
                </div>

                {{-- Opciones de Checkbox --}}
                <div class="md:col-span-12 flex flex-wrap gap-6 pt-2">
                    <label class="inline-flex items-center cursor-pointer gap-2">
                        <input type="checkbox" name="maneja_numero_serie" value="1" {{ old('maneja_numero_serie') ? 'checked' : '' }}
                               class="rounded bg-slate-950 border-slate-800 text-blue-600 focus:ring-0 focus:ring-offset-0">
                        <span class="text-sm text-slate-300">Maneja Número de Serie</span>
                    </label>

                    <label class="inline-flex items-center cursor-pointer gap-2">
                        <input type="checkbox" name="visible_catalogo" value="1" {{ old('visible_catalogo', '1') == '1' ? 'checked' : '' }}
                               class="rounded bg-slate-950 border-slate-800 text-blue-600 focus:ring-0 focus:ring-offset-0">
                        <span class="text-sm text-slate-300">Visible en Catálogo</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Sección de Atributos Técnicos Dinámicos --}}
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-xl space-y-4">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider border-b border-slate-800 pb-3">
                Atributos Técnicos
            </h3>

            <div id="contenedor-atributos" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <p class="text-xs text-slate-500 md:col-span-3">Seleccione una categoría para cargar sus atributos específicos.</p>
            </div>
        </div>

        {{-- Botón Guardar --}}
        <div class="flex justify-end">
            <button type="submit" 
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold rounded-lg transition-colors shadow-lg shadow-blue-600/20">
                Guardar Producto
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('categoria_id').addEventListener('change', function () {
    const categoriaId = this.value;
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
                let html = `<div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        ${attr.nombre} ${attr.unidad ? '<span class="text-slate-500">(' + attr.unidad + ')</span>' : ''}
                    </label>`;

                if (attr.tipo_dato === 'string') {
                    html += `<input type="text" name="atributos[${attr.id}]" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">`;
                } else if (attr.tipo_dato === 'integer') {
                    html += `<input type="number" step="1" name="atributos[${attr.id}]" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">`;
                } else if (attr.tipo_dato === 'decimal') {
                    html += `<input type="number" step="0.0001" name="atributos[${attr.id}]" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">`;
                } else if (attr.tipo_dato === 'boolean') {
                    html += `<select name="atributos[${attr.id}]" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="">N/A</option>
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>`;
                } else if (attr.tipo_dato === 'enum') {
                    html += `<select name="atributos[${attr.id}]" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        <option value="">Seleccione...</option>`;
                    attr.valores_predefinidos.forEach(val => {
                        html += `<option value="${val.id}">${val.valor}</option>`;
                    });
                    html += `</select>`;
                }

                html += `</div>`;
                contenedor.insertAdjacentHTML('beforeend', html);
            });
        });
});
</script>
@endsection