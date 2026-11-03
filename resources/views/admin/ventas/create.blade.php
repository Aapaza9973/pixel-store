@extends('layouts.app')

@section('title', 'Nueva venta')
@section('subtitle', 'Punto de venta')

@section('content')
<div class="-mx-6 -my-6 min-h-full bg-surface" x-data="carrito()"
     data-descuento-maximo="{{ $descuentoMaximo }}"
     data-url-store="{{ route('admin.ventas.store') }}"
     data-url-clientes="{{ route('admin.clientes.buscar') }}"
     data-url-cliente-store="{{ route('admin.clientes.store') }}"
     data-url-series="{{ route('admin.productos.series', ['producto' => '__producto__']) }}"
     data-url-buscar-productos="{{ route('admin.ventas.buscar-productos') }}"
     data-url-comprobante="{{ route('admin.ventas.comprobante', ['venta' => '__venta__']) }}">

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

                {{-- Buscador de productos (HU-5.3) --}}
                <div class="border-b border-outline-variant/30 px-5 py-4">
                    <label for="producto-busqueda" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Buscar producto
                    </label>

                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-outline"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                  d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                        </svg>

                        <input id="producto-busqueda" type="search" x-ref="buscadorProducto"
                               x-model="busquedaProducto"
                               x-on:input.debounce.300ms="buscarProductos()"
                               placeholder="Nombre, SKU o código de barras"
                               autocomplete="off"
                               class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low py-2 pl-9 pr-3 font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    </div>

                    <p x-show="buscandoProducto" x-cloak class="mt-2 font-body-sm text-body-sm text-outline">
                        Buscando…
                    </p>

                    <p x-show="errorProducto" x-cloak class="mt-2 font-body-sm text-body-sm text-error"
                       x-text="errorProducto"></p>

                    <ul x-show="resultadosProducto.length > 0" x-cloak
                        class="mt-2 max-h-72 space-y-1 overflow-y-auto">
                        <template x-for="producto in resultadosProducto" :key="producto.id">
                            <li>
                                <button type="button" x-on:click="agregarProductoBuscado(producto)"
                                        x-bind:disabled="producto.stock < 1"
                                        class="flex w-full items-center justify-between gap-3 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 text-left transition hover:border-primary-container/40 disabled:cursor-not-allowed disabled:border-outline-variant/20 disabled:bg-transparent disabled:opacity-50">
                                    <span class="min-w-0">
                                        <span class="block truncate font-body-md text-body-md text-on-surface"
                                              x-text="producto.nombre"></span>
                                        <span class="block truncate font-label-sm text-label-sm text-outline"
                                              x-text="[producto.sku, producto.categoria].filter(Boolean).join(' · ')"></span>
                                    </span>

                                    <span class="shrink-0 text-right">
                                        <span class="block font-label-md text-label-md tabular-nums text-on-surface"
                                              x-text="money(producto.precio_unitario)"></span>
                                        <span class="block font-label-sm text-label-sm"
                                              x-bind:class="producto.stock < 1 ? 'text-error' : 'text-emerald-400'"
                                              x-text="producto.stock < 1 ? 'Sin stock' : producto.stock + ' en stock'"></span>
                                    </span>
                                </button>
                            </li>
                        </template>
                    </ul>

                    <p x-show="busquedaProducto.trim().length >= 2 && ! buscandoProducto && ! errorProducto && resultadosProducto.length === 0"
                       x-cloak class="mt-2 font-body-sm text-body-sm text-on-surface-variant">
                        Sin resultados para esa búsqueda.
                    </p>
                </div>

                {{-- Tope de stock del carrito (R4) --}}
                <div x-show="avisoStock" x-cloak
                     class="border-b border-amber-500/40 bg-amber-500/10 px-5 py-3 font-body-sm text-body-sm text-amber-400"
                     x-text="avisoStock"></div>

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
                            Buscá un producto por nombre, SKU o código de barras para agregarlo a la venta.
                        </p>
                        <button type="button" x-on:click="enfocarBuscador()"
                                class="mt-2 inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-4 py-2.5 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                            Buscar producto
                        </button>
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
                                        <td class="px-5 py-3.5 text-on-surface">
                                            <div x-text="item.nombre"></div>

                                            {{-- Número de serie (HU-5.6) --}}
                                            <template x-if="item.maneja_numero_serie">
                                                <div class="mt-1 flex items-center gap-2">
                                                    <template x-if="item.numero_serie_id">
                                                        <span class="inline-flex items-center gap-1 rounded-obsidian border border-primary-container/40 bg-primary-container/10 px-2 py-0.5 font-label-sm text-label-sm text-primary-container">
                                                            <span x-text="item.numero_serie || ('SN #' + item.numero_serie_id)"></span>
                                                            <button type="button" x-on:click="quitarSerie(index)"
                                                                    aria-label="Quitar número de serie"
                                                                    class="text-on-surface-variant transition hover:text-error">
                                                                ×
                                                            </button>
                                                        </span>
                                                    </template>

                                                    <button type="button" x-show="! item.numero_serie_id"
                                                            x-on:click="abrirSeries(index)"
                                                            class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline transition hover:text-primary-container">
                                                        Elegir serie
                                                    </button>
                                                </div>
                                            </template>
                                        </td>
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

                {{-- Cliente de la venta (HU-5.5) --}}
                <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-headline-sm text-headline-sm text-on-surface">Cliente</h3>
                        @can('crear clientes')
                            <button type="button" x-on:click="abrirModalCliente()"
                                    class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-primary-container transition hover:text-primary-container/80">
                                Cliente rápido
                            </button>
                        @endcan
                    </div>

                    {{-- Cliente seleccionado --}}
                    <template x-if="cliente">
                        <div class="mt-3 flex items-center justify-between gap-2 rounded-obsidian border border-primary-container/40 bg-primary-container/10 px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate font-label-md text-label-md text-on-surface"
                                   x-text="'Cliente: ' + cliente.nombre"></p>
                                <p class="truncate font-body-sm text-body-sm text-on-surface-variant"
                                   x-text="cliente.nit_ci || cliente.telefono || 'Sin NIT/CI'"></p>
                            </div>
                            <button type="button" x-on:click="limpiarCliente()" aria-label="Quitar cliente"
                                    class="shrink-0 text-body-md text-on-surface-variant transition hover:text-error">
                                ×
                            </button>
                        </div>
                    </template>

                    {{-- Buscador --}}
                    <template x-if="! cliente">
                        <div class="mt-3">
                            <label for="cliente-busqueda" class="sr-only">Buscar cliente</label>
                            <input id="cliente-busqueda" type="search" x-model="busquedaCliente"
                                   x-on:input.debounce.300ms="buscarClientes()"
                                   placeholder="Nombre, NIT/CI o teléfono"
                                   class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">

                            <p x-show="buscandoCliente" x-cloak class="mt-2 font-body-sm text-body-sm text-outline">
                                Buscando…
                            </p>

                            <ul x-show="resultadosCliente.length > 0" x-cloak
                                class="mt-2 max-h-48 space-y-1 overflow-y-auto">
                                <template x-for="resultado in resultadosCliente" :key="resultado.id">
                                    <li>
                                        <button type="button" x-on:click="seleccionarCliente(resultado)"
                                                class="flex w-full items-center justify-between gap-3 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 text-left transition hover:border-primary-container/40">
                                            <span class="truncate font-body-md text-body-md text-on-surface" x-text="resultado.nombre"></span>
                                            <span class="shrink-0 font-label-sm text-label-sm text-outline"
                                                  x-text="resultado.nit_ci || resultado.telefono || ''"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>

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

                    <ul x-show="pagos.length > 0" x-cloak class="mt-3 space-y-2">
                        <template x-for="(pago, index) in pagos" :key="index">
                            <li class="space-y-2 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <select x-model="pago.metodo" aria-label="Método de pago"
                                            class="w-32 rounded-obsidian border border-outline-variant/30 bg-surface-container-lowest px-2 py-1.5 font-body-sm text-body-sm text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                                        <option>Efectivo</option>
                                        <option>Tarjeta</option>
                                        <option>Transferencia</option>
                                        <option>QR</option>
                                        <option>Otro</option>
                                    </select>

                                    <input type="number" min="0" step="0.01" x-model.number="pago.monto"
                                           aria-label="Monto del pago"
                                           class="min-w-0 flex-1 rounded-obsidian border border-outline-variant/30 bg-surface-container-lowest px-2 py-1.5 text-right font-label-md text-label-md tabular-nums text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">

                                    <button type="button" x-on:click="eliminarPago(index)"
                                            aria-label="Quitar pago"
                                            class="shrink-0 text-body-md text-on-surface-variant transition hover:text-error">
                                        ×
                                    </button>
                                </div>

                                <input type="text" x-model="pago.referencia"
                                       placeholder="Referencia (opcional)"
                                       aria-label="Referencia del pago"
                                       class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-lowest px-2 py-1.5 font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                            </li>
                        </template>
                    </ul>

                    <div class="mt-3 flex items-center gap-2">
                        <select x-model="nuevoPago.metodo" aria-label="Método sugerido"
                                class="w-32 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-2 py-2 font-body-sm text-body-sm text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                            <option>Efectivo</option>
                            <option>Tarjeta</option>
                            <option>Transferencia</option>
                            <option>QR</option>
                            <option>Otro</option>
                        </select>

                        <button type="button" x-on:click="agregarPago()"
                                class="flex-1 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-4 py-2 text-body-md text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
                            + Agregar pago
                        </button>
                    </div>

                    <button type="button" x-on:click="pagoExacto()"
                            x-bind:disabled="saldoPendiente <= 0"
                            class="mt-2 w-full rounded-obsidian border border-primary-container/40 bg-primary-container/10 px-4 py-2 font-label-md text-label-md text-primary-container transition hover:bg-primary-container/20 disabled:cursor-not-allowed disabled:border-outline-variant/30 disabled:bg-transparent disabled:text-outline">
                        Pago exacto
                    </button>

                    <dl class="mt-4 space-y-2 border-t border-outline-variant/15 pt-3 font-body-md text-body-md">
                        <div class="flex items-center justify-between">
                            <dt class="text-on-surface-variant">Total pagado</dt>
                            <dd class="tabular-nums text-on-surface" x-text="money(totalPagado)"></dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-on-surface-variant">Saldo pendiente</dt>
                            <dd class="tabular-nums" x-text="money(saldoPendiente)"
                                x-bind:class="faltaPago ? 'text-amber-400' : 'text-emerald-400'"></dd>
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

                            <a x-bind:href="urlComprobante" target="_blank" rel="noopener"
                               class="mt-3 inline-flex items-center gap-2 rounded-obsidian border border-emerald-500/40 bg-emerald-500/10 px-3 py-1.5 font-label-sm text-label-sm uppercase tracking-[0.14em] text-emerald-400 transition hover:bg-emerald-500/20">
                                Ver comprobante
                            </a>
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
                            x-bind:disabled="items.length === 0 || pagos.length === 0 || enviando || faltaPago">
                        <span x-show="! enviando">Confirmar venta</span>
                        <span x-show="enviando" x-cloak>Confirmando…</span>
                    </button>
                </div>
            </div>
        </aside>
    </div>

    {{-- Modal de selección de número de serie (HU-5.6) --}}
    <div x-show="serieModal.abierto" x-cloak
         x-on:keydown.escape.window="serieModal.abierto = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-md rounded-obsidian-xl border border-outline-variant/30 bg-surface-container p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Número de serie
                    </p>
                    <h3 class="mt-1 font-headline-sm text-headline-sm text-on-surface">
                        Elegir serie
                    </h3>
                </div>
                <button type="button" x-on:click="serieModal.abierto = false" aria-label="Cerrar"
                        class="text-body-lg text-on-surface-variant transition hover:text-error">
                    ×
                </button>
            </div>

            <p x-show="serieModal.cargando" x-cloak class="mt-4 font-body-sm text-body-sm text-outline">
                Cargando series…
            </p>

            <p x-show="serieModal.error" x-cloak class="mt-4 font-body-sm text-body-sm text-error"
               x-text="serieModal.error"></p>

            <p x-show="! serieModal.cargando && ! serieModal.error && serieModal.series.length === 0" x-cloak
               class="mt-4 font-body-sm text-body-sm text-on-surface-variant">
                Este producto no tiene series disponibles.
            </p>

            <ul x-show="serieModal.series.length > 0" x-cloak class="mt-4 max-h-64 space-y-1 overflow-y-auto">
                <template x-for="serie in serieModal.series" :key="serie.id">
                    <li>
                        <button type="button" x-on:click="elegirSerie(serie)"
                                class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 text-left font-label-md text-label-md tabular-nums text-on-surface transition hover:border-primary-container/40">
                            <span x-text="serie.numero_serie"></span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    {{-- Modal de alta rápida de cliente (HU-5.5) --}}
    @can('crear clientes')
    <div x-show="modalCliente" x-cloak
         x-on:keydown.escape.window="modalCliente = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-lg rounded-obsidian-xl border border-outline-variant/30 bg-surface-container p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Venta rápida
                    </p>
                    <h3 class="mt-1 font-headline-sm text-headline-sm text-on-surface">Cliente rápido</h3>
                </div>
                <button type="button" x-on:click="modalCliente = false" aria-label="Cerrar"
                        class="text-body-lg text-on-surface-variant transition hover:text-error">
                    ×
                </button>
            </div>

            <form class="mt-5 space-y-4" x-on:submit.prevent="guardarClienteRapido()">
                <div>
                    <label for="rapido-nombre" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Nombre <span class="text-error">*</span>
                    </label>
                    <input id="rapido-nombre" type="text" x-model="nuevoCliente.nombre"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="rapido-nit" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                            NIT / CI
                        </label>
                        <input id="rapido-nit" type="text" x-model="nuevoCliente.nit_ci"
                               class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    </div>
                    <div>
                        <label for="rapido-telefono" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                            Teléfono
                        </label>
                        <input id="rapido-telefono" type="text" x-model="nuevoCliente.telefono"
                               class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    </div>
                </div>

                <div>
                    <label for="rapido-email" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Email
                    </label>
                    <input id="rapido-email" type="email" x-model="nuevoCliente.email"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                </div>

                <div x-show="errorCliente" x-cloak
                     class="rounded-obsidian border border-error/40 bg-error/10 px-3 py-2 font-body-sm text-body-sm text-error"
                     x-text="errorCliente"></div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" x-on:click="modalCliente = false"
                            class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2 text-body-md text-on-surface-variant transition hover:text-on-surface">
                        Cancelar
                    </button>
                    <button type="submit" x-bind:disabled="guardandoCliente"
                            class="rounded-obsidian-lg bg-primary-container px-4 py-2 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90 disabled:cursor-not-allowed disabled:opacity-40">
                        <span x-show="! guardandoCliente">Guardar cliente</span>
                        <span x-show="guardandoCliente" x-cloak>Guardando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan
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
            avisoStock: null,

            // HU-5.5: cliente de la venta.
            cliente: null,
            busquedaCliente: '',
            resultadosCliente: [],
            buscandoCliente: false,
            modalCliente: false,
            guardandoCliente: false,
            nuevoCliente: { nombre: '', telefono: '', nit_ci: '', email: '' },
            errorCliente: null,
            urlClientes: '',
            urlClienteStore: '',

            // HU-5.3: buscador de productos del POS.
            urlBuscarProductos: '',
            urlComprobanteBase: '',
            busquedaProducto: '',
            resultadosProducto: [],
            buscandoProducto: false,
            errorProducto: null,

            // HU-5.6: número de serie por línea.
            urlSeries: '',
            serieModal: { abierto: false, itemIndex: null, cargando: false, series: [], error: null },
            silenciarBanner: false,

            init() {
                // Límite de descuento del rol, inyectado desde Blade.
                this.descuentoMaximo = Number(this.$el.dataset.descuentoMaximo) || 0;
                this.urlStore = this.$el.dataset.urlStore || '';
                this.urlClientes = this.$el.dataset.urlClientes || '';
                this.urlClienteStore = this.$el.dataset.urlClienteStore || '';
                this.urlSeries = this.$el.dataset.urlSeries || '';
                this.urlBuscarProductos = this.$el.dataset.urlBuscarProductos || '';
                this.urlComprobanteBase = this.$el.dataset.urlComprobante || '';
                this.operacionId = this.operacionActual();

                this.cargar();

                // deep: las filas de pago y de carrito se editan en el lugar.
                this.$watch('items', () => {
                    this.persistir();
                    this.limpiarBanner();
                }, { deep: true });

                this.$watch('pagos', () => {
                    this.persistir();
                    this.limpiarBanner();
                }, { deep: true });

                this.$watch('descuento', () => this.persistir(), { deep: true });
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

            // Comparación en centavos: evita falsos pendientes por redondeo.
            get faltaPago() {
                return Math.round(this.saldoPendiente * 100) > 0;
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

            // El banner se limpia al tocar el carrito, así que el link vive
            // dentro del x-if y desaparece con él.
            get urlComprobante() {
                if (! this.ventaConfirmada || ! this.urlComprobanteBase) {
                    return '#';
                }

                return this.urlComprobanteBase.replace('__venta__', this.ventaConfirmada.id);
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
                    stock: Number(producto.stock ?? 0),
                    maneja_numero_serie: Boolean(producto.maneja_numero_serie),
                    numero_serie_id: null,
                    numero_serie: null,
                });
            },

            eliminarItem(index) {
                this.items.splice(index, 1);
                this.avisoStock = null;
            },

            async buscarProductos() {
                const termino = (this.busquedaProducto || '').trim();
                this.errorProducto = null;

                // Mismo umbral que el buscador de clientes: no se consulta por
                // una sola letra.
                if (termino.length < 2) {
                    this.resultadosProducto = [];

                    return;
                }

                this.buscandoProducto = true;

                try {
                    const respuesta = await fetch(this.urlBuscarProductos + '?q=' + encodeURIComponent(termino), {
                        headers: { 'Accept': 'application/json' },
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (! respuesta.ok) {
                        this.resultadosProducto = [];
                        this.errorProducto = cuerpo.message || 'No se pudieron buscar productos.';

                        return;
                    }

                    this.resultadosProducto = cuerpo.data || [];
                } catch (error) {
                    this.resultadosProducto = [];
                    this.errorProducto = 'No se pudo conectar con el servidor.';
                } finally {
                    this.buscandoProducto = false;
                }
            },

            agregarProductoBuscado(producto) {
                if (! producto || Number(producto.stock) < 1) {
                    return;
                }

                // agregarItem ya incrementa la cantidad si el producto está en
                // el carrito, en vez de duplicar la fila.
                this.agregarItem({
                    producto_id: producto.id,
                    nombre: producto.nombre,
                    precio: producto.precio_unitario,
                    cantidad: 1,
                    maneja_numero_serie: producto.maneja_numero_serie,
                });

                this.busquedaProducto = '';
                this.resultadosProducto = [];

                this.$nextTick(() => this.enfocarBuscador());
            },

            enfocarBuscador() {
                this.$refs.buscadorProducto?.focus();
            },

            cambiarCantidad(index, cantidad) {
                const item = this.items[index];

                if (! item) {
                    return;
                }

                let nueva = Math.max(parseInt(cantidad, 10) || 1, 1);

                // Tope de stock (R4). Un stock 0 o ausente se trata como
                // desconocido para no bloquear carritos viejos de sessionStorage;
                // la autoridad final sigue siendo el 409 de VentaService.
                const tope = item.stock == null ? null : Number(item.stock);

                if (tope !== null && tope > 0 && nueva > tope) {
                    nueva = tope;
                    this.avisoStock = 'Solo quedan ' + tope + ' unidades de ' + item.nombre + '.';
                } else if (this.avisoStock) {
                    this.avisoStock = null;
                }

                item.cantidad = nueva;
                item.subtotal = (Number(item.precio) || 0) * nueva;

                this.items.splice(index, 1, item);
            },

            agregarPago() {
                this.pagos.push({ metodo: this.nuevoPago.metodo || 'Efectivo', monto: 0, referencia: '' });
            },

            pagoExacto() {
                const saldo = Number(this.saldoPendiente.toFixed(2));

                if (saldo <= 0) {
                    return;
                }

                this.pagos.push({ metodo: this.nuevoPago.metodo || 'Efectivo', monto: saldo, referencia: '' });
            },

            eliminarPago(index) {
                this.pagos.splice(index, 1);
            },

            async buscarClientes() {
                const termino = (this.busquedaCliente || '').trim();

                if (termino.length < 2) {
                    this.resultadosCliente = [];

                    return;
                }

                this.buscandoCliente = true;

                try {
                    const respuesta = await fetch(this.urlClientes + '?q=' + encodeURIComponent(termino), {
                        headers: { 'Accept': 'application/json' },
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    this.resultadosCliente = respuesta.ok ? (cuerpo.data || []) : [];
                } catch (error) {
                    this.resultadosCliente = [];
                } finally {
                    this.buscandoCliente = false;
                }
            },

            seleccionarCliente(cliente) {
                this.cliente = cliente;
                this.busquedaCliente = '';
                this.resultadosCliente = [];
            },

            limpiarCliente() {
                this.cliente = null;
            },

            abrirModalCliente() {
                this.nuevoCliente = { nombre: '', telefono: '', nit_ci: '', email: '' };
                this.errorCliente = null;
                this.modalCliente = true;
            },

            async guardarClienteRapido() {
                if (this.guardandoCliente) {
                    return;
                }

                this.guardandoCliente = true;
                this.errorCliente = null;

                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

                try {
                    const respuesta = await fetch(this.urlClienteStore, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({
                            nombre: this.nuevoCliente.nombre,
                            telefono: this.nuevoCliente.telefono || null,
                            nit_ci: this.nuevoCliente.nit_ci || null,
                            email: this.nuevoCliente.email || null,
                            activo: true,
                        }),
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (respuesta.status === 201) {
                        this.seleccionarCliente(cuerpo.data);
                        this.modalCliente = false;

                        return;
                    }

                    if (respuesta.status === 422) {
                        this.errorCliente = Object.values(cuerpo.errors || {}).flat().join(' ') || 'Revisá los datos del cliente.';

                        return;
                    }

                    if (respuesta.status === 403) {
                        this.errorCliente = 'No tenés permiso para crear clientes. Consultá con un administrador.';

                        return;
                    }

                    this.errorCliente = cuerpo.message || 'No se pudo crear el cliente.';
                } catch (error) {
                    this.errorCliente = 'No se pudo conectar con el servidor.';
                } finally {
                    this.guardandoCliente = false;
                }
            },

            async abrirSeries(index) {
                const item = this.items[index];

                if (! item) {
                    return;
                }

                this.serieModal = { abierto: true, itemIndex: index, cargando: true, series: [], error: null };

                try {
                    const url = this.urlSeries.replace('__producto__', item.producto_id);
                    const respuesta = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (! respuesta.ok) {
                        this.serieModal.error = cuerpo.message || 'No se pudieron cargar las series.';

                        return;
                    }

                    this.serieModal.series = cuerpo.data || [];
                } catch (error) {
                    this.serieModal.error = 'No se pudo conectar con el servidor.';
                } finally {
                    this.serieModal.cargando = false;
                }
            },

            elegirSerie(serie) {
                const index = this.serieModal.itemIndex;
                const item = this.items[index];

                if (! item) {
                    return;
                }

                item.numero_serie_id = serie.id;
                item.numero_serie = serie.numero_serie;
                this.items.splice(index, 1, item);
                this.serieModal.abierto = false;
            },

            quitarSerie(index) {
                const item = this.items[index];

                if (! item) {
                    return;
                }

                item.numero_serie_id = null;
                item.numero_serie = null;
                this.items.splice(index, 1, item);
            },

            limpiarBanner() {
                if (this.silenciarBanner) {
                    return;
                }

                this.ventaConfirmada = null;
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
                                referencia: (pago.referencia || '').trim() || null,
                            })),
                            meta: {
                                operacion_id: this.operacionId,
                                cliente_id: this.cliente?.id ?? null,
                            },
                            descuento_porcentaje: this.porcentajeParaEnviar,
                        }),
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (respuesta.status === 201) {
                        // El carrito se limpia, pero el banner debe sobrevivir:
                        // los watchers de items/pagos lo borrarían.
                        this.silenciarBanner = true;
                        this.items = [];
                        this.pagos = [];
                        this.descuento = { tipo: '%', valor: 0 };
                        this.operacionId = this.nuevaOperacion();

                        await this.$nextTick();

                        this.silenciarBanner = false;
                        // Número y TOTAL del servidor: evita drift de redondeo.
                        this.ventaConfirmada = cuerpo.venta;

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
                        // Los carritos previos a R4 no guardan stock: queda en null
                        // y el clamp no aplica (compatibilidad).
                        this.items = (Array.isArray(guardado.items) ? guardado.items : []).map((item) => ({
                            ...item,
                            stock: Number.isFinite(item.stock) ? Number(item.stock) : null,
                        }));
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
