@extends('layouts.app')

@section('title', 'Nueva orden de compra')
@section('subtitle', 'Compras')

@section('content')
<div class="max-w-5xl"
     x-data="ordenCompra()"
     data-productos="{{ json_encode($productos->map(fn ($producto) => ['id' => $producto->id, 'nombre' => $producto->nombre, 'precio' => (float) $producto->precio_unitario])->values()) }}"
     data-items="{{ json_encode(old('items', [])) }}"
     data-impuestos="{{ (float) old('impuestos', 0) }}">

    <form method="POST" action="{{ route('admin.compras.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <h3 class="mb-4 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Proveedor y fechas</h3>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="md:col-span-2">
                    <label for="proveedor_id" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Proveedor <span class="text-error">*</span>
                    </label>
                    <select name="proveedor_id" id="proveedor_id"
                            class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                        <option value="">Seleccioná un proveedor</option>
                        @foreach ($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" {{ (string) old('proveedor_id') === (string) $proveedor->id ? 'selected' : '' }}>
                                {{ $proveedor->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('proveedor_id') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="fecha_emision" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Fecha de emisión <span class="text-error">*</span></label>
                    <input type="date" name="fecha_emision" id="fecha_emision" required value="{{ old('fecha_emision', now()->toDateString()) }}"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    @error('fecha_emision') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="fecha_entrega" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Fecha de entrega <span class="text-error">*</span></label>
                    <input type="date" name="fecha_entrega" id="fecha_entrega" required value="{{ old('fecha_entrega') }}"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    @error('fecha_entrega') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <h3 class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ítems</h3>

                <div class="flex items-center gap-2">
                    <select x-model="seleccionado" aria-label="Producto a agregar"
                            class="min-w-[260px] rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-sm text-body-sm text-on-surface">
                        <option value="">Agregar producto…</option>
                        <template x-for="producto in catalogo" :key="producto.id">
                            <option :value="producto.id" x-text="producto.nombre + ' — ' + money(producto.precio)"></option>
                        </template>
                    </select>
                    <button type="button" x-on:click="agregar()" :disabled="! seleccionado"
                            class="rounded-obsidian bg-primary-container px-3 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-primary-container transition hover:bg-primary-container/90 disabled:cursor-not-allowed disabled:opacity-40">
                        Agregar
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-body-md">
                    <thead class="bg-surface-container-low">
                        <tr class="text-left">
                            <th class="px-4 py-3 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                            <th class="w-28 px-4 py-3 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cantidad</th>
                            <th class="w-36 px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Precio unit.</th>
                            <th class="w-32 px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Subtotal</th>
                            <th class="w-16 px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/15">
                        <template x-for="(item, index) in items" :key="item.producto_id">
                            <tr>
                                <td class="px-4 py-3 text-on-surface">
                                    <span x-text="item.nombre"></span>
                                    <input type="hidden" :name="`items[${index}][producto_id]`" :value="item.producto_id">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" min="1" x-model.number="item.cantidad" x-on:change="recalcular(item)"
                                           :name="`items[${index}][cantidad]`" aria-label="Cantidad"
                                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-2 py-1.5 text-center font-label-md text-label-md tabular-nums text-on-surface">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" min="0" step="0.01" x-model.number="item.precio_unitario" x-on:change="recalcular(item)"
                                           :name="`items[${index}][precio_unitario]`" aria-label="Precio unitario"
                                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-2 py-1.5 text-right font-label-md text-label-md tabular-nums text-on-surface">
                                </td>
                                <td class="px-4 py-3 text-right font-label-md text-label-md tabular-nums text-on-surface" x-text="money(item.subtotal)"></td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" x-on:click="quitar(index)" aria-label="Quitar ítem"
                                            class="font-body-sm text-body-sm text-error transition hover:text-on-surface">✕</button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="items.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center font-body-md text-body-md text-outline">
                                Todavía no agregaste productos.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @error('items') <p class="mt-2 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
            @error('items.*') <p class="mt-2 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror

            <div class="mt-6 flex justify-end">
                <dl class="w-full max-w-xs space-y-2 font-body-md text-body-md">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-on-surface-variant">Subtotal</dt>
                        <dd class="tabular-nums text-on-surface" x-text="money(subtotal)"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-on-surface-variant">Impuestos</dt>
                        <dd>
                            <input type="number" min="0" step="0.01" name="impuestos" x-model.number="impuestos" aria-label="Impuestos"
                                   class="w-28 rounded-obsidian border-outline-variant/40 bg-surface-container-low px-2 py-1 text-right font-label-md text-label-md tabular-nums text-on-surface">
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-outline-variant/20 pt-2">
                        <dt class="font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface">Total</dt>
                        <dd class="font-headline-sm text-headline-sm tabular-nums text-on-surface" x-text="money(total)"></dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <label for="notas" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Notas</label>
            <textarea name="notas" id="notas" rows="3"
                      class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">{{ old('notas') }}</textarea>
            @error('notas') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" x-bind:disabled="items.length === 0"
                    class="rounded-obsidian-lg bg-primary-container px-4 py-2 font-body-md text-body-md text-on-primary-container transition hover:bg-primary-container/90 disabled:cursor-not-allowed disabled:opacity-40">
                Guardar orden
            </button>
            <a href="{{ route('admin.compras.index') }}"
               class="rounded-obsidian-lg bg-surface-container px-4 py-2 font-body-md text-body-md text-on-surface-variant transition hover:text-on-surface">
                Cancelar
            </a>
        </div>
    </form>
</div>

@once
    <script>
        function ordenCompra() {
            return {
                catalogo: [],
                seleccionado: '',
                items: [],
                impuestos: 0,

                init() {
                    try {
                        this.catalogo = JSON.parse(this.$el.dataset.productos || '[]');
                    } catch (error) {
                        this.catalogo = [];
                    }

                    try {
                        const guardados = JSON.parse(this.$el.dataset.items || '[]');
                        this.items = (Array.isArray(guardados) ? guardados : []).map((item) => {
                            const producto = this.catalogo.find((p) => Number(p.id) === Number(item.producto_id));
                            const cantidad = Math.max(parseInt(item.cantidad, 10) || 1, 1);
                            const precio = Math.max(Number(item.precio_unitario ?? producto?.precio ?? 0) || 0, 0);

                            return {
                                producto_id: Number(item.producto_id),
                                nombre: producto?.nombre || 'Producto',
                                cantidad: cantidad,
                                precio_unitario: precio,
                                subtotal: this.redondear(cantidad * precio),
                            };
                        });
                    } catch (error) {
                        this.items = [];
                    }

                    this.impuestos = Math.max(Number(this.$el.dataset.impuestos) || 0, 0);
                },

                redondear(valor) {
                    return Math.round((Number(valor) || 0) * 100) / 100;
                },

                money(valor) {
                    return 'Bs ' + this.redondear(valor).toFixed(2);
                },

                agregar() {
                    const producto = this.catalogo.find((p) => Number(p.id) === Number(this.seleccionado));

                    if (! producto) {
                        return;
                    }

                    const existente = this.items.find((item) => item.producto_id === producto.id);

                    if (existente) {
                        existente.cantidad += 1;
                        this.recalcular(existente);
                    } else {
                        const precio = Math.max(Number(producto.precio) || 0, 0);

                        this.items.push({
                            producto_id: producto.id,
                            nombre: producto.nombre,
                            cantidad: 1,
                            precio_unitario: precio,
                            subtotal: this.redondear(precio),
                        });
                    }

                    this.seleccionado = '';
                },

                recalcular(item) {
                    item.cantidad = Math.max(parseInt(item.cantidad, 10) || 1, 1);
                    item.precio_unitario = Math.max(Number(item.precio_unitario) || 0, 0);
                    item.subtotal = this.redondear(item.cantidad * item.precio_unitario);
                },

                quitar(index) {
                    this.items.splice(index, 1);
                },

                get subtotal() {
                    return this.redondear(this.items.reduce((suma, item) => suma + (Number(item.subtotal) || 0), 0));
                },

                get total() {
                    return this.redondear(this.subtotal + Math.max(Number(this.impuestos) || 0, 0));
                },
            };
        }
    </script>
@endonce
@endsection
