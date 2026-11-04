@props(['producto', 'favorito' => false])

<button type="button"
        x-data="{ fav: {{ $favorito ? 'true' : 'false' }}, enviando: false }"
        x-on:click.prevent.stop="
            if (enviando) return;
            enviando = true;
            const previo = fav;
            fav = ! fav;
            fetch(@js(route('catalogo.favoritos.toggle', $producto)), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                credentials: 'same-origin',
            })
                .then((respuesta) => respuesta.ok ? respuesta.json() : Promise.reject(respuesta))
                .then((datos) => { fav = Boolean(datos.favorito); })
                .catch(() => { fav = previo; })
                .finally(() => { enviando = false; });
        "
        x-bind:aria-pressed="fav ? 'true' : 'false'"
        x-bind:class="fav
            ? 'border-error/60 bg-error/10 text-error'
            : 'border-outline-variant/30 bg-surface-container-lowest/70 text-outline hover:text-error'"
        class="inline-flex h-9 w-9 items-center justify-center rounded-full border backdrop-blur-xl transition"
        aria-label="Guardar {{ $producto->nombre }} en favoritos"
        title="Guardar en favoritos">
    <svg class="h-4 w-4" x-bind:fill="fav ? 'currentColor' : 'none'"
         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
              d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
    </svg>
</button>
