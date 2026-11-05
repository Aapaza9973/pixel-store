@extends('layouts.app')

@section('title', 'Nueva cotización')
@section('subtitle', 'Registrar una cotización formal')

@section('content')
<div class="max-w-4xl" x-data="cotizacionForm()">
    <form method="POST" action="{{ route('admin.cotizaciones.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <h3 class="mb-4 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cliente</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="cliente_id" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cliente existente</label>
                    <select name="cliente_id" id="cliente_id"
                            class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                        <option value="">Cliente nuevo / manual</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>{{ $cliente->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="nombre_cliente" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Nombre <span class="text-error">*</span></label>
                    <input type="text" name="nombre_cliente" id="nombre_cliente" value="{{ old('nombre_cliente') }}"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    @error('nombre_cliente') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nit_ci" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">NIT / CI</label>
                    <input type="text" name="nit_ci" id="nit_ci" value="{{ old('nit_ci') }}"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                </div>
                <div>
                    <label for="telefono" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Teléfono</label>
                    <input type="text" name="telefono" id="telefono" value="{{ old('telefono') }}"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                </div>
                <div>
                    <label for="validez_dias" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Validez (días)</label>
                    <input type="number" name="validez_dias" id="validez_dias" value="{{ old('validez_dias', 4) }}" min="1" max="365"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                </div>
                <div>
                    <label for="descuento" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Descuento (Bs)</label>
                    <input type="number" step="0.01" name="descuento" id="descuento" value="{{ old('descuento', 0) }}" min="0"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                </div>
            </div>
        </div>

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ítems</h3>
                <button type="button" @click="agregar()"
                        class="rounded-obsidian bg-primary-container px-3 py-1.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-primary-container transition hover:bg-primary-container/90">
                    Agregar ítem
                </button>
            </div>

            <template x-for="(item, index) in items" :key="index">
                <div class="mb-3 grid grid-cols-12 items-end gap-2">
                    <div class="col-span-4">
                        <select x-model="item.producto_id" :name="`items[${index}][producto_id]`" @change="elegirProducto(index, $event)"
                                class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-sm text-body-sm text-on-surface">
                            <option value="">Ítem manual</option>
                            @foreach ($productos as $p)
                                <option value="{{ $p->id }}" data-nombre="{{ $p->nombre }}" data-precio="{{ $p->precio_unitario }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-3">
                        <input type="text" x-model="item.nombre" :name="`items[${index}][nombre]`" placeholder="Nombre"
                               class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-sm text-body-sm text-on-surface">
                    </div>
                    <div class="col-span-2">
                        <input type="number" step="0.01" x-model.number="item.precio_unitario" :name="`items[${index}][precio_unitario]`" placeholder="Precio"
                               class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-sm text-body-sm text-on-surface">
                    </div>
                    <div class="col-span-2">
                        <input type="number" min="1" x-model.number="item.cantidad" :name="`items[${index}][cantidad]`" placeholder="Cant."
                               class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-sm text-body-sm text-on-surface">
                    </div>
                    <div class="col-span-1 text-right">
                        <button type="button" @click="quitar(index)" class="font-body-sm text-body-sm text-error transition hover:text-on-surface">✕</button>
                    </div>
                </div>
            </template>
            @error('items') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
            @error('items.*') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-obsidian-lg bg-primary-container px-4 py-2 font-body-md text-body-md text-on-primary-container transition hover:bg-primary-container/90">Guardar cotización</button>
            <a href="{{ route('admin.cotizaciones.index') }}" class="rounded-obsidian-lg bg-surface-container px-4 py-2 font-body-md text-body-md text-on-surface-variant transition hover:text-on-surface">Cancelar</a>
        </div>
    </form>
</div>

@once
    <script>
        function cotizacionForm() {
            return {
                items: [{ producto_id: '', nombre: '', precio_unitario: 0, cantidad: 1 }],
                agregar() {
                    this.items.push({ producto_id: '', nombre: '', precio_unitario: 0, cantidad: 1 });
                },
                quitar(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },
                elegirProducto(index, event) {
                    const opt = event.target.selectedOptions[0];
                    this.items[index].nombre = opt.dataset.nombre || '';
                    this.items[index].precio_unitario = parseFloat(opt.dataset.precio || 0);
                },
            };
        }
    </script>
@endonce
@endsection
