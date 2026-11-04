@php
    $imagenCard = $producto->imagenesGaleria->firstWhere('es_principal', true)
        ?? $producto->imagenesGaleria->first();
@endphp

<a href="{{ route('catalogo.producto', $producto->slug) }}"
   class="block overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl transition hover:border-primary/50">
    @if ($imagenCard)
        <img src="{{ $imagenCard->urlThumbnail() }}"
             alt="{{ $imagenCard->alt ?? $producto->nombre }}"
             class="aspect-square w-full object-cover">
    @else
        <div class="flex aspect-square w-full items-center justify-center bg-surface-container-low">
            <span class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Sin imagen</span>
        </div>
    @endif

    <div class="space-y-1 p-3">
        <p class="line-clamp-2 font-body-sm text-body-sm text-on-surface">{{ $producto->nombre }}</p>
        <p class="font-label-md text-label-md tabular-nums text-on-surface">
            Bs {{ number_format((float) $producto->precio_unitario, 2, ',', '.') }}
        </p>
    </div>
</a>
