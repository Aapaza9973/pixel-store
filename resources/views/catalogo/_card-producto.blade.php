@php
    $imagenCard = $producto->imagenesGaleria->firstWhere('es_principal', true)
        ?? $producto->imagenesGaleria->first();
@endphp

<div class="relative overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl transition hover:border-primary-container/50">
    <a href="{{ route('catalogo.producto', $producto->slug) }}" class="block">
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
                {{ \App\Support\Dinero::formatear((float) $producto->precio_unitario) }}
            </p>
        </div>
    </a>

    <div class="absolute right-2 top-2">
        @include('catalogo._boton-favorito', [
            'producto' => $producto,
            'favorito' => in_array((int) $producto->id, $favoritosIds ?? [], true),
        ])
    </div>

    <div class="border-t border-outline-variant/20 px-3 py-2.5">
        @if ((int) $producto->stock > 0)
            <button type="button"
                    x-data="{ agregado: false }"
                    x-on:click="agregar({
                        producto_id: {{ (int) $producto->id }},
                        nombre: @js($producto->nombre),
                        precio: {{ (float) $producto->precio_unitario }},
                        imagen: @js($imagenCard?->urlThumbnail()),
                        stock: {{ (int) $producto->stock }},
                        slug: @js($producto->slug),
                    }); agregado = true; setTimeout(() => agregado = false, 1200)"
                    class="w-full rounded-obsidian-lg border border-primary-container/40 bg-primary-container/10 px-3 py-2 font-label-sm text-label-sm uppercase tracking-[0.14em] text-secondary transition hover:bg-primary-container/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60">
                <span x-show="! agregado">Agregar</span>
                <span x-show="agregado" x-cloak>Agregado</span>
            </button>
        @else
            <button type="button" disabled
                    class="w-full cursor-not-allowed rounded-obsidian-lg border border-outline-variant/30 px-3 py-2 font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">
                Sin stock
            </button>
        @endif
    </div>
</div>
