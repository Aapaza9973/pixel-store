@props(['producto'])
@php($agotado = $producto->stock < 1)
<article class="panel flex flex-col overflow-hidden rounded transition hover:border-cyan-400/60">
    <a href="{{ route('shop.show', $producto->ref) }}" class="block aspect-[4/3] bg-black/30">
        @if ($producto->imagen_url)
            <img src="{{ $producto->imagen_url }}" alt="{{ $producto->nombre }}" loading="lazy" class="h-full w-full object-contain p-3 sm:p-4">
        @else
            <div class="flex h-full items-center justify-center text-sm muted">Sin imagen</div>
        @endif
    </a>
    <div class="flex flex-1 flex-col gap-2 p-3 sm:p-4">
        <p class="text-xs muted">{{ $producto->marca }} · {{ $producto->categoria }}</p>
        <h3 class="font-display text-sm font-semibold leading-snug sm:text-base">
            <a href="{{ route('shop.show', $producto->ref) }}" class="hover:underline">{{ $producto->nombre }}</a>
        </h3>
        <div class="mt-auto flex items-end justify-between gap-2 pt-2">
            <p class="font-display text-lg font-bold sm:text-xl" style="color:var(--cyan)">Bs. {{ number_format($producto->precio_unitario, 2) }}</p>
            <p class="text-xs {{ $agotado ? 'text-red-400' : 'muted' }}">{{ $agotado ? 'Agotado' : $producto->stock.' disp.' }}</p>
        </div>
        <form action="{{ route('cart.store', $producto->id) }}" method="POST">
            @csrf
            <button class="btn w-full text-sm" @disabled($agotado)>Agregar</button>
        </form>
    </div>
</article>
