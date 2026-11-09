@extends('layouts.shop')

@section('content')
<section class="mb-10 max-w-2xl">
    <h1 class="font-display text-3xl font-bold leading-tight sm:text-4xl">Componentes y equipos para armar la PC que necesitas</h1>
    <p class="muted mt-3">Filtra por categoría, marca, precio y disponibilidad, y haz tu pedido en minutos.</p>
    <a href="{{ route('shop.index') }}" class="btn mt-5">Ver catálogo</a>
</section>

<h2 class="font-display mb-4 text-xl font-semibold sm:text-2xl">Recién llegados</h2>
<div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
    @foreach ($destacados as $producto)
        <x-shop.product-card :producto="$producto" />
    @endforeach
</div>
@endsection
