@extends('layouts.shop')
@section('title', 'Carrito | Tu Tienda PC')

@section('content')
<h1 class="font-display mb-6 text-2xl font-bold sm:text-3xl">Tu carrito</h1>

@if ($lines->isEmpty())
    <div class="panel rounded p-10 text-center">
        <p class="font-display text-lg">Aún no agregaste productos</p>
        <a href="{{ route('shop.index') }}" class="btn mt-4">Explorar el catálogo</a>
    </div>
@else
<div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
    <div class="panel divide-y rounded">
        @foreach ($lines as $l)
            <div class="flex flex-wrap items-center gap-3 p-4" style="border-color:var(--line)">
                <div class="min-w-0 flex-1 basis-48">
                    <a href="{{ route('shop.show', $l->producto->ref) }}" class="font-display font-semibold hover:underline">{{ $l->producto->nombre }}</a>
                    <p class="muted text-sm">Bs. {{ number_format($l->producto->precio_unitario, 2) }} c/u</p>
                </div>
                <form action="{{ route('cart.update', $l->producto->id) }}" method="POST" class="flex items-center gap-2">
                    @csrf @method('PATCH')
                    <label class="sr-only" for="c{{ $l->producto->id }}">Cantidad</label>
                    <input id="c{{ $l->producto->id }}" type="number" inputmode="numeric" name="cantidad" value="{{ $l->cantidad }}" min="0" max="{{ min(99, $l->producto->stock) }}" class="input w-20">
                    <button class="btn btn-ghost">Actualizar</button>
                </form>
                <p class="font-display ml-auto w-28 text-right font-bold">Bs. {{ number_format($l->subtotal(), 2) }}</p>
                <form action="{{ route('cart.destroy', $l->producto->id) }}" method="POST">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-400 hover:underline" aria-label="Quitar {{ $l->producto->nombre }}">Quitar</button>
                </form>
            </div>
        @endforeach
    </div>

    <aside class="panel h-fit rounded p-5">
        <p class="muted text-sm">Total</p>
        <p class="font-display text-3xl font-bold" style="color:var(--cyan)">Bs. {{ number_format($total, 2) }}</p>
        <a href="{{ route('checkout.create') }}" class="btn mt-5 w-full">Continuar con el pedido</a>
    </aside>
</div>
@endif
@endsection
