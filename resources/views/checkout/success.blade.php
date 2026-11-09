@extends('layouts.shop')
@section('title', 'Pedido recibido | Tu Tienda PC')

@section('content')
<div class="panel mx-auto max-w-xl rounded p-6 text-center sm:p-8">
    <h1 class="font-display text-2xl font-bold sm:text-3xl">Recibimos tu pedido</h1>
    <p class="muted mt-2">Pedido N.º <span class="font-display font-bold" style="color:var(--cyan)">{{ $pedido->id }}</span> · estado: {{ $pedido->estado }}</p>
    <ul class="mx-auto mt-5 max-w-sm space-y-1 text-left text-sm">
        @foreach ($pedido->items as $i)
            <li class="flex justify-between gap-3"><span>{{ $i->cantidad }} × {{ $i->nombre }}</span><span>Bs. {{ number_format($i->cantidad * $i->precio_unitario, 2) }}</span></li>
        @endforeach
    </ul>
    <p class="font-display mt-4 text-xl font-bold">Total: Bs. {{ number_format($pedido->total, 2) }}</p>
    <p class="muted mt-3 text-sm">Te contactaremos al {{ $pedido->telefono }} para coordinar el pago y la entrega. Guarda este enlace para ver tu pedido.</p>
    <a href="{{ route('shop.index') }}" class="btn mt-6">Seguir comprando</a>
</div>
@endsection
