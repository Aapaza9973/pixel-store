@extends('layouts.app')

@section('title', 'Productos')
@section('subtitle', 'Catálogo e inventario de productos')

@section('content')
@php
    $categoriaSeleccionada = $categorias->firstWhere('id', (int) request('categoria_id'));
    $marcaSeleccionada = $marcas->firstWhere('id', (int) request('marca_id'));

    $disponibilidadLabels = [
        'con_stock' => 'Con stock',
        'agotado' => 'Agotado',
        'bajo_stock' => 'Bajo stock',
    ];

    $atributosActivos = collect((array) request('atributos', []))
        ->filter(fn ($valor) => $valor !== null && $valor !== '');

    $hayFiltros = request()->anyFilled([
        'buscar', 'categoria_id', 'marca_id', 'precio_min', 'precio_max', 'disponibilidad',
    ]) || $atributosActivos->isNotEmpty();

    $atributosPorId = collect($atributosFiltrables)->flatten(1)->keyBy('id');

    $urlSin = function (array $claves) {
        $query = collect(request()->query())->except('page')->all();
        foreach ($claves as $clave) {
            unset($query[$clave]);
        }

        return route('admin.productos.index', $query);
    };

    $urlSinAtributo = function ($atributoId) {
        $query = collect(request()->query())->except('page')->all();
        unset($query['atributos'][$atributoId]);
        if (empty($query['atributos'])) {
            unset($query['atributos']);
        }

        return route('admin.productos.index', $query);
    };

    $etiquetaValorAtributo = function ($atributoId, $valor) use ($atributosPorId) {
        $atributo = $atributosPorId->get($atributoId);

        if (($atributo['tipo_dato'] ?? null) === 'enum') {
            $opcion = collect($atributo['valores'] ?? [])->firstWhere('id', (int) $valor);

            return $opcion['valor'] ?? $valor;
        }

        return $valor;
    };
@endphp

<div class="space-y-6"
     x-data="{
         filtrosAbiertos: {{ $hayFiltros ? 'true' : 'false' }},
         buscar: @js((string) request('buscar', '')),
         categoriaId: @js((string) request('categoria_id', '')),
         atributosFiltrables: @js($atributosFiltrables),
         valoresAtributos: @js((object) request('atributos', [])),
         get atributosCategoria() {
             return this.atributosFiltrables[this.categoriaId] || [];
         },
         etiqueta(atributo) {
             return atributo.unidad ? atributo.nombre + ' (' + atributo.unidad + ')' : atributo.nombre;
         }
     }">

    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-on-surface tracking-tight">Listado de productos</h2>

        @can('crear productos')
            <a href="{{ route('admin.productos.create') }}"
               class="px-4 py-2 bg-primary-container hover:bg-primary-container/90 text-on-primary-container text-sm font-medium rounded-obsidian-lg transition">
                Nuevo producto
            </a>
        @endcan
    </div>

    {{-- Filtros Tarea 11 --}}
    <x-card>
        <form method="GET" action="{{ route('admin.productos.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label for="buscar" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Buscar
                    </label>
                    <input type="text" name="buscar" id="buscar" placeholder="Nombre, SKU o código de barras..."
                           x-model="buscar" @input.debounce.400ms="$el.form.submit()"
                           class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                </div>

                <div>
                    <label for="categoria_id" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Categoría
                    </label>
                    <select name="categoria_id" id="categoria_id" x-model="categoriaId"
                            class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                        <option value="">Todas</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected(request('categoria_id') == $categoria->id)>
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="marca_id" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Marca
                    </label>
                    <select name="marca_id" id="marca_id"
                            class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                        <option value="">Todas</option>
                        @foreach ($marcas as $marca)
                            <option value="{{ $marca->id }}" @selected(request('marca_id') == $marca->id)>
                                {{ $marca->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="orden" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Ordenar por
                    </label>
                    <select name="orden" id="orden"
                            class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                        <option value="recientes" @selected(request('orden', 'recientes') === 'recientes')>Más recientes</option>
                        <option value="nombre_asc" @selected(request('orden') === 'nombre_asc')>Nombre A-Z</option>
                        <option value="nombre_desc" @selected(request('orden') === 'nombre_desc')>Nombre Z-A</option>
                        <option value="precio_asc" @selected(request('orden') === 'precio_asc')>Precio: menor a mayor</option>
                        <option value="precio_desc" @selected(request('orden') === 'precio_desc')>Precio: mayor a menor</option>
                        <option value="stock_bajo" @selected(request('orden') === 'stock_bajo')>Stock: menor a mayor</option>
                    </select>
                </div>
            </div>

            {{-- Fila 2: precio y disponibilidad --}}
            <div x-show="filtrosAbiertos" x-transition class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1">
                <div>
                    <label for="precio_min" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Precio mínimo
                    </label>
                    <input type="number" step="0.01" min="0" name="precio_min" id="precio_min"
                           value="{{ request('precio_min') }}"
                           class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                </div>

                <div>
                    <label for="precio_max" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Precio máximo
                    </label>
                    <input type="number" step="0.01" min="0" name="precio_max" id="precio_max"
                           value="{{ request('precio_max') }}"
                           class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                </div>

                <div>
                    <label for="disponibilidad" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-1.5">
                        Disponibilidad
                    </label>
                    <select name="disponibilidad" id="disponibilidad"
                            class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                        <option value="">Todas</option>
                        <option value="con_stock" @selected(request('disponibilidad') === 'con_stock')>Con stock</option>
                        <option value="agotado" @selected(request('disponibilidad') === 'agotado')>Agotado</option>
                        <option value="bajo_stock" @selected(request('disponibilidad') === 'bajo_stock')>Bajo stock</option>
                    </select>
                </div>
            </div>

            {{-- Fila 3: atributos filtrables de la categoría seleccionada --}}
            <div x-show="filtrosAbiertos && categoriaId !== ''" x-transition class="pt-1">
                <template x-if="atributosCategoria.length > 0">
                    <div>
                        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">
                            Atributos técnicos
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <template x-for="atributo in atributosCategoria" :key="atributo.id">
                                <div>
                                    <label class="block text-xs text-on-surface-variant mb-1.5" x-text="etiqueta(atributo)"></label>

                                    <template x-if="atributo.tipo_dato === 'enum'">
                                        <select x-model="valoresAtributos[atributo.id]" :name="`atributos[${atributo.id}]`"
                                                class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                                            <option value="">Todos</option>
                                            <template x-for="opcion in atributo.valores" :key="opcion.id">
                                                <option :value="opcion.id" x-text="opcion.valor"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <template x-if="atributo.tipo_dato !== 'enum'">
                                        <input type="text" x-model="valoresAtributos[atributo.id]" :name="`atributos[${atributo.id}]`"
                                               class="w-full px-3 py-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25 focus:outline-none">
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-1">
                <button type="submit"
                        class="px-4 py-2 bg-primary-container hover:bg-primary-container/90 text-on-primary-container text-sm font-medium rounded-obsidian-lg transition">
                    Aplicar filtros
                </button>

                <button type="button" @click="filtrosAbiertos = !filtrosAbiertos"
                        class="px-4 py-2 border border-outline-variant/30 bg-surface-container-low text-on-surface-variant text-sm font-medium rounded-obsidian-lg transition"
                        x-text="filtrosAbiertos ? 'Ocultar filtros' : 'Más filtros'">
                </button>

                @if ($hayFiltros)
                    <a href="{{ route('admin.productos.index') }}"
                       class="text-sm text-on-surface-variant hover:text-on-surface transition">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    {{-- Chips de filtros activos --}}
    @if ($hayFiltros)
        <div class="flex flex-wrap items-center gap-2">
            @if (request()->filled('buscar'))
                <a href="{{ $urlSin(['buscar']) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs border border-outline-variant/30 bg-surface-container-low text-on-surface-variant rounded-full border-outline-variant/30 transition">
                    Búsqueda: {{ request('buscar') }} <span class="text-outline">×</span>
                </a>
            @endif

            @if ($categoriaSeleccionada)
                <a href="{{ $urlSin(['categoria_id', 'atributos']) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs border border-outline-variant/30 bg-surface-container-low text-on-surface-variant rounded-full border-outline-variant/30 transition">
                    Categoría: {{ $categoriaSeleccionada->nombre }} <span class="text-outline">×</span>
                </a>
            @endif

            @if ($marcaSeleccionada)
                <a href="{{ $urlSin(['marca_id']) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs border border-outline-variant/30 bg-surface-container-low text-on-surface-variant rounded-full border-outline-variant/30 transition">
                    Marca: {{ $marcaSeleccionada->nombre }} <span class="text-outline">×</span>
                </a>
            @endif

            @if (request()->filled('precio_min') || request()->filled('precio_max'))
                <a href="{{ $urlSin(['precio_min', 'precio_max']) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs border border-outline-variant/30 bg-surface-container-low text-on-surface-variant rounded-full border-outline-variant/30 transition">
                    Precio: {{ request('precio_min', '0') }} - {{ request('precio_max', '∞') }} <span class="text-outline">×</span>
                </a>
            @endif

            @if (request()->filled('disponibilidad'))
                <a href="{{ $urlSin(['disponibilidad']) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs border border-outline-variant/30 bg-surface-container-low text-on-surface-variant rounded-full border-outline-variant/30 transition">
                    Disponibilidad: {{ $disponibilidadLabels[request('disponibilidad')] ?? request('disponibilidad') }} <span class="text-outline">×</span>
                </a>
            @endif

            @foreach ($atributosActivos as $atributoId => $valor)
                <a href="{{ $urlSinAtributo($atributoId) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 text-xs border border-outline-variant/30 bg-surface-container-low text-on-surface-variant rounded-full border-outline-variant/30 transition">
                    {{ $atributosPorId->get($atributoId)['nombre'] ?? 'Atributo' }}: {{ $etiquetaValorAtributo($atributoId, $valor) }} <span class="text-outline">×</span>
                </a>
            @endforeach
        </div>
    @endif

    <x-card>
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-on-surface-variant">
                Mostrando <span class="text-on-surface font-medium">{{ $productos->total() }}</span> productos
                @if ($hayFiltros)
                    <span class="text-outline">(filtrados)</span>
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-outline-variant/15 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-outline">
                        <th class="px-3 py-3">Producto</th>
                        <th class="px-3 py-3">SKU</th>
                        <th class="px-3 py-3">Categoría</th>
                        <th class="px-3 py-3">Marca</th>
                        <th class="px-3 py-3 text-right">Precio</th>
                        <th class="px-3 py-3 text-center">Stock</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15">
                    @forelse ($productos as $producto)
                        <tr class="hover:bg-primary-container/[0.04] transition">
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($producto->imagen_principal)
                                        <img src="{{ asset('storage/'.$producto->imagen_principal) }}"
                                             alt="{{ $producto->nombre }}"
                                             class="w-10 h-10 object-cover rounded-obsidian-lg border border-outline-variant/15">
                                    @else
                                        <div class="w-10 h-10 rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low flex items-center justify-center">
                                            <svg class="w-5 h-5 text-outline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="text-on-surface font-medium truncate">{{ $producto->nombre }}</p>
                                        @if (! $producto->visible_catalogo)
                                            <span class="inline-flex mt-1 px-2 py-0.5 text-[10px] font-semibold uppercase rounded-full bg-surface-container-low text-on-surface-variant">
                                                Oculto
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-on-surface-variant">{{ $producto->sku ?? '—' }}</td>
                            <td class="px-3 py-3 text-on-surface-variant">{{ $producto->categoria->nombre ?? '—' }}</td>
                            <td class="px-3 py-3 text-on-surface-variant">{{ $producto->marca->nombre ?? 'Sin marca' }}</td>
                            <td class="px-3 py-3 text-right text-emerald-400 font-medium">
                                {{ \App\Support\Dinero::formatear($producto->precio_unitario) }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($producto->tieneStockBajo())
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border border-error/25 bg-error/10 text-error">
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
                                           class="text-xs text-on-surface-variant hover:text-on-surface transition">Ver</a>
                                    @endcan

                                    @can('editar productos')
                                        <a href="{{ route('admin.productos.edit', $producto) }}"
                                           class="text-xs text-secondary hover:text-on-surface transition">Editar</a>
                                    @endcan

                                    @can('eliminar productos')
                                        <form method="POST" action="{{ route('admin.productos.destroy', $producto) }}"
                                              onsubmit="return confirm('¿Está seguro de eliminar este producto?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-error hover:text-error/80 transition">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-10 text-center text-sm text-outline">
                                No hay productos que coincidan con los filtros aplicados.
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
