@extends('layouts.app')

@section('title', 'Nueva venta')
@section('subtitle', 'Punto de venta')

@section('content')
<div class="-mx-6 -my-6 min-h-full bg-surface" x-data="carrito()"
     data-descuento-maximo="{{ $descuentoMaximo }}"
     data-url-store="{{ route('admin.ventas.store') }}">

    {{-- Barra superior --}}
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-outline-variant/30 px-6 py-4">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                POS · Venta
            </p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">
                Nueva venta
            </h2>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-1.5 font-label-md text-label-md text-on-surface-variant tabular-nums">
                <span class="h-1.5 w-1.5 rounded-full bg-tertiary"></span>
                <span x-text="items.length"></span> ítems
            </span>

            <a href="{{ route('admin.productos.index') }}"
               class="inline-flex items-center gap-2 rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2 text-body-md text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
                Buscar producto
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-3">

        {{-- CARRITO --}}
        <section class="lg:col-span-2">
            <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
                <div class="flex items-center justify-between border-b border-outline-variant/30 px-5 py-4">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Carrito</h3>
                    <span class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline"
                          x-text="items.length + ' línea(s)'"></span>
                </div>

                {{-- Estado vacío --}}
                <template x-if="items.length === 0">
                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-20 text-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low text-outline">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 006 19h11M9 22a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/>
                            </svg>
                        </div>
                        <p class="font-headline-sm text-headline-sm text-on-surface">El carrito está vacío</p>
                        <p class="max-w-sm font-body-sm text-body-sm text-on-surface-variant">
                            Agregá productos para comenzar la venta. La búsqueda con lector de código de barras llega en el Día 2.
                        </p>
                        <a href="{{ route('admin.productos.index') }}"
                           class="mt-2 inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-4 py-2.5 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                            Buscar producto
                        </a>
                    </div>
                </template>

                {{-- Líneas --}}
                <template x-if="items.length > 0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-body-md">
                            <thead class="bg-surface-container-low">
                                <tr>
                                    <th class="px-5 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                                    <th class="px-5 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Precio</th>
                                    <th class="px-5 py-3 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cantidad</th>
                                    <th class="px-5 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Subtotal</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/15">
                                <template x-for="(item, index) in items" :key="item.producto_id">
                                    <tr class="transition hover:bg-primary-container/[0.04]">
                                        <td class="px-5 py-3.5 text-on-surface" x-text="item.nombre"></td>
                                        <td class="px-5 py-3.5 text-right font-label-md text-label-md tabular-nums text-on-surface-variant"
                                            x-text="money(item.precio)"></td>
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button"
                                                        class="h-7 w-7 rounded-obsidian border border-outline-variant/30 text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface"
                                                        x-on:click="cambiarCantidad(index, item.cantidad - 1)"
                                                        aria-label="Restar una unidad">−</button>
                                                <input type="number" min="1" x-model.number="item.cantidad"
                                                       x-on:change="cambiarCantidad(index, item.cantidad)"
                                                       class="w-16 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-2 py-1 text-center font-label-md text-label-md tabular-nums text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                                                <button type="button"
                                                        class="h-7 w-7 rounded-obsidian border border-outline-variant/30 text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface"
                                                        x-on:click="cambiarCantidad(index, item.cantidad + 1)"
                                                        aria-label="Sumar una unidad">+</button>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-label-md text-label-md tabular-nums text-on-surface"
                                            x-text="money(item.subtotal)"></td>
                                        <td class="px-5 py-3.5 text-right">
                                            <button type="button"
                                                    class="text-body-sm text-error transition hover:text-error/80"
                                                    x-on:click="eliminarItem(index)">
                                                Quitar
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </section>

        {{-- PANEL DE PAGO --}}
        <aside class="lg:col-span-1">
            <div class="lg:sticky lg:top-6 space-y-6">

                <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
                    <h3 class="font-headline-sm text-headline-sm text-on-surface">Resumen</h3>

                    <dl class="mt-4 space-y-2 font-body-md text-body-md">
                        <div class="flex items-center justify-between">
                            <dt class="text-on-surface-variant">Subtotal</dt>
                            <dd class="tabular-nums text-on-surface" x-text="money(subtotal)"></dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-on-surface-variant">Descuento</dt>
                            <dd class="tabular-nums text-on-surface" x-text="'- ' + money(descuentoAplicado)"></dd>
                        </div>
                        <div class="flex items-center justify-between border-t border-outline-variant/15 pt-3">
                            <dt class="font-headline-sm text-headline-sm text-on-surface">Total</dt>
                            <dd class="font-headline-sm text-headline-sm tabular-nums text-on-surface" x-text="money(total)"></dd>
                        </div>
                    </dl>

                    {{-- Descuento por rol (HU-5.10) --}}
                    <div class="mt-5 border-t border-outline-variant/15 pt-4">
                        <div class="flex items-center justify-between">
                            <label for="descuento-valor" class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                                Descuento
                            </label>
                            <span class="font-label-sm text-label-sm text-outline"
                                  x-text="'Máximo ' + descuentoMaximo + '%'"></span>
                        </div>

                        <div class="mt-2 grid grid-cols-3 gap-2">
                            <select id="descuento-tipo" x-model="descuento.tipo"
                                    class="rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                                <option value="%">%</option>
                                <option value="Bs">Bs</option>
                            </select>
                            <input id="descuento-valor" type="number" min="0" step="0.01" x-model.number="descuento.valor"
                                   class="col-span-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-label-md text-label-md tabular-nums text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                        </div>

                        <p x-show="descuentoExcedeLimite" x-cloak
                           class="mt-2 font-body-sm text-body-sm text-amber-400">
                            Descuento requiere autorización de administrador.
                        </p>
                    </div>
                </div>

                <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
                    <div class="flex items-center justify-between">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Pagos</h3>
                        <span class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline"
                              x-text="pagos.length + ' registro(s)'"></span>
                    </div>

                    <template x-if="pagos.length === 0">
                        <p class="mt-3 font-body-sm text-body-sm text-on-surface-variant">
                            Todavía no registraste pagos.
                        </p>
                    </template>

                    <ul class="mt-3 space-y-2">
                        <template x-for="(pago, index) in pagos" :key="index">
                            <li class="flex items-center justify-between rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2">
                                <span class="font-label-md text-label-md text-on-surface-variant" x-text="pago.metodo"></span>
                                <div class="flex items-center gap-3">
                                    <span class="font-label-md text-label-md tabular-nums text-on-surface" x-text="money(pago.monto)"></span>
                                    <button type="button" class="text-body-sm text-error transition hover:text-error/80"
                                            x-on:click="eliminarPago(index)">
                                        Quitar
                                    </button>
                                </div>
                            </li>
                        </template>
                    </ul>

                    <form class="mt-4 space-y-3" x-on:submit.prevent="agregarPago(nuevoPago.metodo, nuevoPago.monto)">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="metodo-pago" class="block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline mb-1">
                                    Método
                                </label>
                                <select id="metodo-pago" x-model="nuevoPago.metodo"
                                        class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                                    <option>Efectivo</option>
                                    <option>Tarjeta</option>
                                    <option>Transferencia</option>
                                    <option>QR</option>
                                    <option>Otro</option>
                                </select>
                            </div>
                            <div>
                                <label for="monto-pago" class="block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline mb-1">
                                    Monto
                                </label>
                                <input id="monto-pago" type="number" min="0" step="0.01" x-model.number="nuevoPago.monto"
                                       class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-label-md text-label-md tabular-nums text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-4 py-2 text-body-md text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
                            Agregar pago
                        </button>
                    </form>

                    <dl class="mt-4 space-y-2 border-t border-outline-variant/15 pt-3 font-body-md text-body-md">
                        <div class="flex items-center justify-between">
                            <dt class="text-on-surface-variant">Total pagado</dt>
                            <dd class="tabular-nums text-on-surface" x-text="money(totalPagado)"></dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-on-surface-variant">Saldo pendiente</dt>
                            <dd class="tabular-nums" x-text="money(saldoPendiente)"
                                x-bind:class="saldoPendiente > 0 ? 'text-amber-400' : 'text-emerald-400'"></dd>
                        </div>
                    </dl>
                </div>

                {{-- Confirmación: POST real a admin.ventas.store --}}
                <div class="space-y-3">
                    <template x-if="ventaConfirmada">
                        <div class="rounded-obsidian-xl border border-emerald-500/30 bg-emerald-500/10 p-4">
                            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-emerald-400">
                                Venta confirmada
                            </p>
                            <p class="mt-1 font-headline-sm text-headline-sm text-on-surface"
                               x-text="'Venta #' + ventaConfirmada.id"></p>
                            <p class="mt-1 font-body-md text-body-md tabular-nums text-emerald-400"
                               x-text="'Total cobrado: ' + money(ventaConfirmada.total)"></p>
                        </div>
                    </template>

                    <div x-show="errorGeneral" x-cloak
                         class="rounded-obsidian border border-error/40 bg-error/10 px-3 py-2 font-body-sm text-body-sm text-error"
                         x-text="errorGeneral"></div>

                    <ul x-show="erroresCampos.length > 0" x-cloak
                        class="space-y-1 rounded-obsidian border border-error/40 bg-error/10 px-3 py-2 font-body-sm text-body-sm text-error">
                        <template x-for="mensaje in erroresCampos" :key="mensaje">
                            <li x-text="mensaje"></li>
                        </template>
                    </ul>

                    <p x-show="pagos.length === 0" x-cloak class="font-body-sm text-body-sm text-outline">
                        Continuar al pago requiere registrar al menos un pago.
                    </p>

                    <button type="button" x-on:click="confirmarVenta()"
                            class="w-full rounded-obsidian-lg bg-primary-container px-4 py-3 text-body-md font-medium text-on-primary-container shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_0_20px_rgba(37,99,235,0.35)] transition hover:bg-primary-container/90 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
                            x-bind:disabled="items.length === 0 || pagos.length === 0 || enviando">
                        <span x-show="! enviando">Confirmar venta</span>
                        <span x-show="enviando" x-cloak>Confirmando…</span>
                    </button>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
    window.carrito = function () {
        return {
            items: [],
            pagos: [],
            nuevoPago: { metodo: 'Efectivo', monto: 0 },
            descuentoMaximo: 0,
            descuento: { tipo: '%', valor: 0 },
            storageKey: 'pos.carrito.v1',
            operacionKey: 'pos.operacion.v1',
            urlStore: '',
            enviando: false,
            errores: {},
            errorGeneral: null,
            ventaConfirmada: null,

            init() {
                // Límite de descuento del rol, inyectado desde Blade.
                this.descuentoMaximo = Number(this.$el.dataset.descuentoMaximo) || 0;
                this.urlStore = this.$el.dataset.urlStore || '';
                this.operacionId = this.operacionActual();

                this.cargar();

                this.$watch('items', () => this.persistir());
                this.$watch('pagos', () => this.persistir());
                this.$watch('descuento', () => this.persistir());
            },

            get subtotal() {
                return this.items.reduce((suma, item) => suma + (Number(item.subtotal) || 0), 0);
            },

            get descuentoMonto() {
                const valor = Number(this.descuento.valor) || 0;

                if (valor <= 0) {
                    return 0;
                }

                return this.descuento.tipo === '%'
                    ? this.subtotal * (valor / 100)
                    : valor;
            },

            get descuentoPorcentaje() {
                return this.subtotal > 0 ? (this.descuentoMonto / this.subtotal) * 100 : 0;
            },

            get descuentoExcedeLimite() {
                return this.descuentoPorcentaje > this.descuentoMaximo + 0.0001;
            },

            // Sin autorización de admin (HU-5.11) el descuento no se aplica.
            get descuentoAplicado() {
                return this.descuentoExcedeLimite ? 0 : this.descuentoMonto;
            },

            get total() {
                return Math.max(this.subtotal - this.descuentoAplicado, 0);
            },

            get totalPagado() {
                return this.pagos.reduce((suma, pago) => suma + (Number(pago.monto) || 0), 0);
            },

            get saldoPendiente() {
                return Math.max(this.total - this.totalPagado, 0);
            },

            // El servidor recibe porcentaje, nunca monto.
            get porcentajeParaEnviar() {
                const valor = Number(this.descuento.valor) || 0;

                if (valor <= 0 || this.subtotal <= 0) {
                    return 0;
                }

                const porcentaje = this.descuento.tipo === '%'
                    ? valor
                    : (valor / this.subtotal) * 100;

                return Math.round(Math.min(Math.max(porcentaje, 0), 100) * 100) / 100;
            },

            get erroresCampos() {
                return Object.values(this.errores || {}).flat();
            },

            agregarItem(producto) {
                const existente = this.items.find((item) => item.producto_id === producto.producto_id);

                if (existente) {
                    this.cambiarCantidad(this.items.indexOf(existente), existente.cantidad + (Number(producto.cantidad) || 1));

                    return;
                }

                const cantidad = Math.max(Number(producto.cantidad) || 1, 1);
                const precio = Number(producto.precio) || 0;

                this.items.push({
                    producto_id: producto.producto_id,
                    nombre: producto.nombre,
                    precio: precio,
                    cantidad: cantidad,
                    subtotal: precio * cantidad,
                });
            },

            eliminarItem(index) {
                this.items.splice(index, 1);
            },

            cambiarCantidad(index, cantidad) {
                const item = this.items[index];

                if (! item) {
                    return;
                }

                const nueva = Math.max(parseInt(cantidad, 10) || 1, 1);
                item.cantidad = nueva;
                item.subtotal = (Number(item.precio) || 0) * nueva;

                this.items.splice(index, 1, item);
            },

            agregarPago(metodo, monto) {
                const importe = Number(monto) || 0;

                if (importe <= 0) {
                    return;
                }

                this.pagos.push({ metodo: metodo, monto: importe });
                this.nuevoPago.monto = 0;
            },

            eliminarPago(index) {
                this.pagos.splice(index, 1);
            },

            operacionActual() {
                const guardada = sessionStorage.getItem(this.operacionKey);

                return guardada || this.nuevaOperacion();
            },

            nuevaOperacion() {
                const token = window.crypto && window.crypto.randomUUID
                    ? window.crypto.randomUUID()
                    : 'pos-' + Date.now() + '-' + Math.random().toString(16).slice(2);

                sessionStorage.setItem(this.operacionKey, token);

                return token;
            },

            async confirmarVenta() {
                if (this.enviando || this.items.length === 0 || this.pagos.length === 0) {
                    return;
                }

                this.enviando = true;
                this.errores = {};
                this.errorGeneral = null;
                this.ventaConfirmada = null;

                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

                try {
                    const respuesta = await fetch(this.urlStore, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({
                            items: this.items.map((item) => ({
                                producto_id: item.producto_id,
                                cantidad: item.cantidad,
                                numero_serie_id: item.numero_serie_id || null,
                            })),
                            pagos: this.pagos.map((pago) => ({
                                metodo: pago.metodo,
                                monto: pago.monto,
                            })),
                            meta: { operacion_id: this.operacionId },
                            descuento_porcentaje: this.porcentajeParaEnviar,
                        }),
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (respuesta.status === 201) {
                        // Número y TOTAL del servidor: evita drift de redondeo.
                        this.ventaConfirmada = cuerpo.venta;
                        this.items = [];
                        this.pagos = [];
                        this.descuento = { tipo: '%', valor: 0 };
                        this.operacionId = this.nuevaOperacion();

                        return;
                    }

                    if (respuesta.status === 422) {
                        this.errores = cuerpo.errors || {};
                        this.errorGeneral = cuerpo.errors
                            ? (cuerpo.message || 'Revisá los datos de la venta.')
                            : (cuerpo.message || 'No se pudo confirmar la venta.');

                        return;
                    }

                    if (respuesta.status === 409) {
                        this.errorGeneral = cuerpo.message || 'El producto ya no está disponible.';

                        return;
                    }

                    this.errorGeneral = cuerpo.message || 'No se pudo confirmar la venta.';
                } catch (error) {
                    this.errorGeneral = 'No se pudo conectar con el servidor. Reintentá la operación.';
                } finally {
                    this.enviando = false;
                }
            },

            cargar() {
                try {
                    const guardado = JSON.parse(sessionStorage.getItem(this.storageKey) || 'null');

                    if (guardado) {
                        this.items = Array.isArray(guardado.items) ? guardado.items : [];
                        this.pagos = Array.isArray(guardado.pagos) ? guardado.pagos : [];

                        if (guardado.descuento) {
                            this.descuento = {
                                tipo: guardado.descuento.tipo === 'Bs' ? 'Bs' : '%',
                                valor: Number(guardado.descuento.valor) || 0,
                            };
                        }
                    }
                } catch (error) {
                    this.items = [];
                    this.pagos = [];
                }
            },

            persistir() {
                sessionStorage.setItem(this.storageKey, JSON.stringify({
                    items: this.items,
                    pagos: this.pagos,
                    descuento: this.descuento,
                }));
            },

            money(valor) {
                return 'Bs ' + (Number(valor) || 0).toFixed(2);
            },
        };
    };
</script>
@endsection
