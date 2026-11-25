{{-- Atributos técnicos dinámicos (EAV) — Tarea 9.6 --}}
<div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 space-y-4">
    <h3 class="text-sm font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/15 pb-3">
        Atributos técnicos
    </h3>

    <p x-show="categoriaSeleccionada === ''" class="text-xs text-outline">
        Seleccione una categoría para cargar sus atributos específicos.
    </p>

    <p x-show="categoriaSeleccionada !== '' && atributosCategoria.length === 0" class="text-xs text-outline">
        Esta categoría no tiene atributos técnicos configurados.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <template x-for="atributo in atributosCategoria" :key="atributo.id">
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider"
                       x-text="etiqueta(atributo)"></label>

                {{-- string --}}
                <template x-if="atributo.tipo_dato === 'string'">
                    <input type="text" x-model="valores[atributo.id]" :name="`atributos[${atributo.id}]`"
                           class="w-full bg-surface border border-outline-variant/15 rounded-obsidian-lg px-4 py-2.5 text-sm text-on-surface focus:outline-none focus:border-primary-container">
                </template>

                {{-- integer --}}
                <template x-if="atributo.tipo_dato === 'integer'">
                    <input type="number" step="1" x-model="valores[atributo.id]" :name="`atributos[${atributo.id}]`"
                           class="w-full bg-surface border border-outline-variant/15 rounded-obsidian-lg px-4 py-2.5 text-sm text-on-surface focus:outline-none focus:border-primary-container">
                </template>

                {{-- decimal --}}
                <template x-if="atributo.tipo_dato === 'decimal'">
                    <input type="number" step="0.01" x-model="valores[atributo.id]" :name="`atributos[${atributo.id}]`"
                           class="w-full bg-surface border border-outline-variant/15 rounded-obsidian-lg px-4 py-2.5 text-sm text-on-surface focus:outline-none focus:border-primary-container">
                </template>

                {{-- enum --}}
                <template x-if="atributo.tipo_dato === 'enum'">
                    <select x-model="valores[atributo.id]" :name="`atributos[${atributo.id}]`"
                            class="w-full bg-surface border border-outline-variant/15 rounded-obsidian-lg px-4 py-2.5 text-sm text-on-surface focus:outline-none focus:border-primary-container">
                        <option value="">Seleccione...</option>
                        <template x-for="opcion in atributo.valores" :key="opcion.id">
                            <option :value="opcion.id" x-text="opcion.valor"></option>
                        </template>
                    </select>
                </template>

                {{-- boolean --}}
                <template x-if="atributo.tipo_dato === 'boolean'">
                    <div class="flex items-center gap-2 pt-1">
                        <input type="hidden" value="0" :name="`atributos[${atributo.id}]`">
                        <input type="checkbox" value="1" :name="`atributos[${atributo.id}]`"
                               :checked="String(valores[atributo.id]) === '1'"
                               class="rounded bg-surface border-outline-variant/15 text-primary-container focus:ring-0">
                        <span class="text-xs text-on-surface-variant">Sí</span>
                    </div>
                </template>
            </div>
        </template>
    </div>

    @error('atributos')
        <p class="text-xs text-error">{{ $message }}</p>
    @enderror
</div>

@once
    {{-- Se define una sola vez por página; Alpine lo lee al inicializar. --}}
    <script>
        function productoForm(config) {
            return {
                atributosPorCategoria: config.atributosPorCategoria || {},
                categoriaSeleccionada: String(config.seleccionada || ''),
                valores: Object.assign({}, config.valores || {}),

                get atributosCategoria() {
                    return this.atributosPorCategoria[this.categoriaSeleccionada] || [];
                },

                etiqueta(atributo) {
                    return atributo.unidad
                        ? atributo.nombre + ' (' + atributo.unidad + ')'
                        : atributo.nombre;
                },
            };
        }
    </script>
@endonce
