@extends('layouts.shop')
@section('title', 'Catálogo | Tu Tienda PC')

@section('content')
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl font-bold sm:text-3xl">Catálogo</h1>
        <p class="muted text-sm">{{ $productos->total() }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}</p>
    </div>

    <form method="GET" action="{{ route('shop.index') }}" class="flex items-center gap-2">
        @foreach (request()->except(['orden', 'page']) as $k => $v)
            @if (is_scalar($v)) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
        @endforeach
        <label for="orden" class="text-sm muted">Ordenar</label>
        <select id="orden" name="orden" class="input w-auto" onchange="this.form.submit()">
            @foreach (['recientes' => 'Más recientes', 'precio_asc' => 'Precio: menor a mayor', 'precio_desc' => 'Precio: mayor a menor', 'nombre' => 'Nombre A–Z'] as $v => $l)
                <option value="{{ $v }}" @selected($filtros->orden === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="grid gap-6 lg:grid-cols-[17rem_1fr]">
    {{-- En celular los filtros se abren/cierran con un botón; en PC siempre visibles --}}
    <aside x-data="{ abierto: false }">
        <button type="button" class="btn btn-ghost w-full lg:hidden" @click="abierto = !abierto" :aria-expanded="abierto">
            <span x-text="abierto ? 'Ocultar filtros' : 'Filtrar productos'">Filtrar productos</span>
        </button>
        <div class="mt-3 lg:mt-0 lg:block" :class="abierto ? '' : 'hidden'">
            @include('shop._filters')
        </div>
    </aside>

    <section>
        @if ($productos->isEmpty())
            <div class="panel rounded p-10 text-center">
                <p class="font-display text-lg">Ningún producto coincide con tu búsqueda</p>
                <p class="muted mt-1 text-sm">Prueba con otro rango de precio o quita algún filtro.</p>
                <a href="{{ route('shop.index') }}" class="btn mt-4">Quitar filtros</a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-3">
                @foreach ($productos as $producto)
                    <x-shop.product-card :producto="$producto" />
                @endforeach
            </div>
            <div class="mt-8">{{ $productos->links() }}</div>
        @endif
    </section>
</div>
@endsection
