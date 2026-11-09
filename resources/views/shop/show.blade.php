@extends('layouts.shop')
@section('title', $producto->nombre.' | Tu Tienda PC')

@section('content')
@php($agotado = $producto->stock < 1)
@php($fotos = count($galeria) ? $galeria : array_filter([$producto->imagen_url]))
<a href="{{ route('shop.index') }}" class="muted text-sm hover:underline">← Volver al catálogo</a>

<div class="mt-4 grid gap-8 md:grid-cols-2">
    <div x-data="{ actual: 0 }">
        <div class="panel flex aspect-square items-center justify-center rounded p-4">
            @forelse ($fotos as $i => $url)
                <img x-show="actual === {{ $i }}" src="{{ $url }}" alt="{{ $producto->nombre }}" class="max-h-full object-contain" @if($i>0) loading="lazy" @endif>
            @empty
                <span class="muted">Sin imagen</span>
            @endforelse
        </div>
        @if (count($fotos) > 1)
            <div class="mt-3 flex gap-2 overflow-x-auto">
                @foreach ($fotos as $i => $url)
                    <button type="button" @click="actual = {{ $i }}" class="panel h-16 w-16 shrink-0 rounded p-1" :class="actual === {{ $i }} ? 'border-cyan-400' : ''" aria-label="Ver imagen {{ $i + 1 }}">
                        <img src="{{ $url }}" alt="" class="h-full w-full object-contain">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        <p class="muted text-sm">{{ $producto->marca }} · {{ $producto->categoria }}</p>
        <h1 class="font-display mt-1 text-2xl font-bold sm:text-3xl">{{ $producto->nombre }}</h1>
        <p class="font-display mt-4 text-3xl font-bold" style="color:var(--cyan)">Bs. {{ number_format($producto->precio_unitario, 2) }}</p>
        <p class="mt-2 text-sm {{ $agotado ? 'text-red-400' : 'muted' }}">{{ $agotado ? 'Agotado' : $producto->stock.' unidades disponibles' }}</p>

        <form action="{{ route('cart.store', $producto->id) }}" method="POST" class="mt-6 flex items-center gap-3">
            @csrf
            <label for="cantidad" class="sr-only">Cantidad</label>
            <input id="cantidad" type="number" inputmode="numeric" name="cantidad" value="1" min="1" max="{{ max(1, min(99, $producto->stock)) }}" class="input w-24" @disabled($agotado)>
            <button class="btn flex-1 sm:flex-none" @disabled($agotado)>Agregar al carrito</button>
        </form>

        @if ($producto->descripcion)
            <div class="muted mt-8 max-w-prose whitespace-pre-line text-sm leading-relaxed">{{ $producto->descripcion }}</div>
        @endif

        @if (count($specs))
            <h2 class="font-display mb-2 mt-8 text-lg font-semibold">Especificaciones</h2>
            <dl class="panel divide-y rounded text-sm" style="border-color:var(--line)">
                @foreach ($specs as $s)
                    <div class="flex justify-between gap-4 px-4 py-2" style="border-color:var(--line)">
                        <dt class="muted">{{ $s['nombre'] }}</dt><dd class="text-right">{{ $s['valor'] }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</div>

@if ($relacionados->isNotEmpty())
    <h2 class="font-display mb-4 mt-14 text-xl font-semibold sm:text-2xl">Productos relacionados</h2>
    <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
        @foreach ($relacionados as $r) <x-shop.product-card :producto="$r" /> @endforeach
    </div>
@endif
@endsection
