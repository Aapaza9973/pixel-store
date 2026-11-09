{{-- Formulario GET: cada cambio recarga con los parámetros en la URL (compartible y paginable). --}}
<form method="GET" action="{{ route('shop.index') }}" class="panel space-y-5 rounded p-4">
    <input type="hidden" name="q" value="{{ $filtros->busqueda }}">
    <input type="hidden" name="orden" value="{{ $filtros->orden }}">

    <div>
        <label for="categoria" class="mb-1 block text-sm font-medium">Categoría</label>
        <select id="categoria" name="categoria" class="input" onchange="this.form.submit()">
            <option value="">Todas</option>
            @foreach ($categorias as $c)
                <option value="{{ $c->id }}" @selected($filtros->categoriaId === (int) $c->id)>{{ $c->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="marca" class="mb-1 block text-sm font-medium">Marca</label>
        <select id="marca" name="marca" class="input" onchange="this.form.submit()">
            <option value="">Todas</option>
            @foreach ($marcas as $m)
                <option value="{{ $m->id }}" @selected($filtros->marcaId === (int) $m->id)>{{ $m->nombre }}</option>
            @endforeach
        </select>
    </div>

    <fieldset>
        <legend class="mb-1 text-sm font-medium">Precio (Bs.)</legend>
        <div class="flex items-center gap-2">
            <input type="number" min="0" step="1" inputmode="numeric" name="precio_min" value="{{ $filtros->precioMin }}" placeholder="Mín." class="input" aria-label="Precio mínimo">
            <span class="muted">–</span>
            <input type="number" min="0" step="1" inputmode="numeric" name="precio_max" value="{{ $filtros->precioMax }}" placeholder="Máx." class="input" aria-label="Precio máximo">
        </div>
        @error('precio_max') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
    </fieldset>

    <label class="flex cursor-pointer items-center gap-2 text-sm">
        <input type="checkbox" name="disponible" value="1" @checked($filtros->soloDisponibles) onchange="this.form.submit()" class="h-5 w-5 accent-cyan-400">
        Solo productos disponibles
    </label>

    <div class="flex gap-2">
        <button class="btn flex-1">Aplicar</button>
        <a href="{{ route('shop.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
</form>
