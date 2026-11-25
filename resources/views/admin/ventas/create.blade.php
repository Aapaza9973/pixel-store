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
     data-url-validar-serie="{{ route('admin.ventas.validar-serie') }}"
     data-url-buscar-productos="{{ route('admin.ventas.buscar-productos') }}"
     data-url-autorizar-descuento="{{ route('admin.ventas.autorizar-descuento') }}"
     data-url-comprobante="{{ route('admin.ventas.comprobante', ['venta' => '__venta__']) }}">

    {{-- Barra superior + stepper del flujo 1-2-3 --}}
    <div class="sticky top-0 z-30 border-b border-outline-variant/30 bg-surface/95 backdrop-blur-xl">
        <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
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
                <span class="hidden rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-1.5 font-label-md text-label-md tabular-nums text-on-surface sm:inline-flex"
                      x-text="money(total)"></span>
            </div>
        </div>

        {{-- Stepper: cada paso es un botón; los saltos se validan en irAPaso() --}}
        <nav aria-label="Pasos de la venta" class="flex gap-2 overflow-x-auto px-6 pb-3">
            <button type="button" x-on:click="irAPaso(1)"
                    class="group flex min-w-[9.5rem] flex-1 items-center gap-3 rounded-obsidian border px-3 py-2 text-left transition"
                    x-bind:class="paso === 1
                        ? 'border-primary-container/60 bg-primary-container/10'
                        : 'border-outline-variant/30 bg-surface-container-low/40 hover:border-outline'">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full font-label-sm text-label-sm tabular-nums"
                      x-bind:class="paso === 1 ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container-low text-outline'">1</span>
                <span class="min-w-0">
                    <span class="block font-label-sm text-label-sm uppercase tracking-[0.14em]"
                          x-bind:class="paso === 1 ? 'text-primary' : 'text-outline'">Buscar</span>
                    <span class="block truncate font-body-sm text-body-sm text-on-surface-variant">Agregar productos</span>
                </span>
            </button>

            <button type="button" x-on:click="irAPaso(2)"
                    class="group flex min-w-[9.5rem] flex-1 items-center gap-3 rounded-obsidian border px-3 py-2 text-left transition"
                    x-bind:class="paso === 2
                        ? 'border-primary-container/60 bg-primary-container/10'
                        : 'border-outline-variant/30 bg-surface-container-low/40 hover:border-outline'">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full font-label-sm text-label-sm tabular-nums"
                      x-bind:class="paso === 2 ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container-low text-outline'"
                      x-text="items.length > 0 ? '2' : '2'">2</span>
                <span class="min-w-0">
                    <span class="block font-label-sm text-label-sm uppercase tracking-[0.14em]"
                          x-bind:class="paso === 2 ? 'text-primary' : 'text-outline'">Carrito</span>
                    <span class="block truncate font-body-sm text-body-sm text-on-surface-variant"
                          x-text="items.length === 0 ? 'Sin ítems' : items.length + ' línea(s)'"></span>
                </span>
            </button>

            <button type="button" x-on:click="irAPaso(3)"
                    class="group flex min-w-[9.5rem] flex-1 items-center gap-3 rounded-obsidian border px-3 py-2 text-left transition"
                    x-bind:class="paso === 3
                        ? 'border-primary-container/60 bg-primary-container/10'
                        : 'border-outline-variant/30 bg-surface-container-low/40 hover:border-outline'">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full font-label-sm text-label-sm tabular-nums"
                      x-bind:class="paso === 3 ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container-low text-outline'">3</span>
                <span class="min-w-0">
                    <span class="block font-label-sm text-label-sm uppercase tracking-[0.14em]"
                          x-bind:class="paso === 3 ? 'text-primary' : 'text-outline'">Cobrar</span>
                    <span class="block truncate font-body-sm text-body-sm text-on-surface-variant"
                          x-text="pagos.length === 0 ? 'Sin pagos' : money(totalPagado) + ' registrado'"></span>
                </span>
            </button>
        </nav>
    </div>

    <div class="p-6">

        {{-- Aviso de flujo (saltos bloqueados) --}}
        <div x-show="avisoFlujo" x-cloak
             class="mb-4 rounded-obsidian border border-amber-500/40 bg-amber-500/10 px-4 py-2.5 font-body-sm text-body-sm text-amber-400"
             x-text="avisoFlujo"></div>

        {{-- ============ PASO 1: BUSCAR ============ --}}
        <section x-show="paso === 1" x-cloak class="space-y-4">
            <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
                <label for="producto-busqueda" class="mb-2 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    Buscar producto
                </label>

                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-outline"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>

                    <input id="producto-busqueda" type="search" x-ref="buscadorProducto"
                           x-model="busquedaProducto"
                           x-on:input.debounce.300ms="buscarProductos()"
                           placeholder="Nombre, SKU o código de barras"
                           autocomplete="off"
                           class="w-full rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low py-3.5 pl-12 pr-4 font-body-lg text-body-lg text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                </div>

                <p x-show="buscandoProducto" x-cloak class="mt-2 font-body-sm text-body-sm text-outline">
                    Buscando…
                </p>

                <p x-show="errorProducto" x-cloak class="mt-2 font-body-sm text-body-sm text-error"
                   x-text="errorProducto"></p>

                <p x-show="busquedaProducto.trim().length >= 2 && ! buscandoProducto && ! errorProducto && resultadosProducto.length === 0"
                   x-cloak class="mt-2 font-body-sm text-body-sm text-on-surface-variant">
                    Sin resultados para esa búsqueda.
                </p>
            </div>

            {{-- Resultados como grilla de tarjetas: un clic agrega al carrito --}}
            <ul x-show="resultadosProducto.length > 0" x-cloak
                class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="producto in resultadosProducto" :key="producto.id">
                    <li>
                        <button type="button" x-on:click="agregarProductoBuscado(producto)"
                                x-bind:disabled="producto.stock < 1"
                                class="flex h-full w-full flex-col justify-between gap-3 rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-lowest/60 p-4 text-left transition hover:border-primary-container/40 hover:bg-surface-container-low/60 disabled:cursor-not-allowed disabled:border-outline-variant/20 disabled:bg-transparent disabled:opacity-50">
                            <span class="min-w-0">
                                <span class="block truncate font-body-md text-body-md text-on-surface"
                                      x-text="producto.nombre"></span>
                                <span class="mt-1 block truncate font-label-sm text-label-sm text-outline"
                                      x-text="[producto.sku, producto.categoria].filter(Boolean).join(' · ')"></span>
                            </span>

                            <span class="flex items-end justify-between gap-2">
                                <span class="font-label-lg text-label-lg tabular-nums text-on-surface"
                                      x-text="money(producto.precio_unitario)"></span>
                                <span class="inline-flex items-center gap-1.5 rounded-obsidian px-2 py-0.5 font-label-sm text-label-sm uppercase tracking-[0.14em]"
                                      x-bind:class="producto.stock < 1
                                          ? 'border border-error/30 bg-error/10 text-error'
                                          : 'border border-emerald-500/25 bg-emerald-500/10 text-emerald-400'">
                                    <span class="h-1.5 w-1.5 rounded-full"
                                          x-bind:class="producto.stock < 1 ? 'bg-error' : 'bg-emerald-500'"></span>
                                    <span x-text="producto.stock < 1 ? 'Sin stock' : producto.stock + ' en stock'"></span>
                                </span>
                            </span>
                        </button>
                    </li>
                </template>
            </ul>

            {{-- Resumen del paso: qué llevás y atajos al siguiente paso --}}
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 px-5 py-4 backdrop-blur-xl">
                <div>
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        En la venta
                    </p>
                    <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
                        <span class="tabular-nums text-on-surface" x-text="items.length"></span> línea(s) ·
                        <span class="tabular-nums text-on-surface" x-text="money(total)"></span>
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="irAPaso(2)"
                            x-bind:disabled="items.length === 0"
                            class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface disabled:cursor-not-allowed disabled:opacity-40">
                        Ver carrito
                    </button>
                    <button type="button" x-on:click="irAPaso(3)"
                            x-bind:disabled="items.length === 0"
                            class="rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-primary-container shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_0_20px_rgba(37,99,235,0.35)] transition hover:bg-primary-container/90 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none">
                        Cobrar
                    </button>
                </div>
            </div>
        </section>

        {{-- ============ PASO 2: CARRITO ============ --}}
        <section x-show="paso === 2" x-cloak class="space-y-4">
            <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
                <div class="flex items-center justify-between border-b border-outline-variant/30 px-5 py-4">
                    <div>
                        <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Paso 2</p>
                        <h3 class="mt-0.5 font-headline-sm text-headline-sm text-on-surface">Carrito</h3>
                    </div>
                    <span class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline"
                          x-text="items.length + ' línea(s)'"></span>
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
                        <button type="button" x-on:click="irAPaso(1)"
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

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-outline-variant/30 px-5 py-4">
                    <button type="button" x-on:click="irAPaso(1)"
                            class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                        Seguir agregando
                    </button>

                    <div class="flex items-center gap-4">
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            Total <span class="ml-1 font-headline-sm text-headline-sm tabular-nums text-on-surface" x-text="money(total)"></span>
                        </p>
                        <button type="button" x-on:click="irAPaso(3)"
                                x-bind:disabled="items.length === 0"
                                class="rounded-obsidian-lg bg-primary-container px-5 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-primary-container shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_0_20px_rgba(37,99,235,0.35)] transition hover:bg-primary-container/90 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none">
                            Continuar al pago
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ PASO 3: COBRAR ============ --}}
        <section x-show="paso === 3" x-cloak>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                <div class="space-y-6 lg:col-span-2">

                    {{-- Cliente de la venta (HU-5.5) --}}
                    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Paso 3</p>
                                <h3 class="mt-0.5 font-headline-sm text-headline-sm text-on-surface">Cliente</h3>
                            </div>
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

                    {{-- Pagos: métodos en un clic + registro manual --}}
                    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
                        <div class="flex items-center justify-between">
                            <h3 class="font-headline-sm text-headline-sm text-on-surface">Pagos</h3>
                            <span class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline"
                                  x-text="pagos.length + ' registro(s)'"></span>
                        </div>

                        <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                            Elegí el método y cobrá el saldo exacto, o cargá los montos a mano.
                        </p>

                        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <button type="button" x-on:click="nuevoPago.metodo = 'Efectivo'; pagoExacto()"
                                    x-bind:disabled="faltaPago === false"
                                    class="rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface disabled:cursor-not-allowed disabled:opacity-40">
                                Efectivo
                            </button>
                            <button type="button" x-on:click="nuevoPago.metodo = 'Tarjeta'; pagoExacto()"
                                    x-bind:disabled="faltaPago === false"
                                    class="rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface disabled:cursor-not-allowed disabled:opacity-40">
                                Tarjeta
                            </button>
                            <button type="button" x-on:click="nuevoPago.metodo = 'Transferencia'; pagoExacto()"
                                    x-bind:disabled="faltaPago === false"
                                    class="rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface disabled:cursor-not-allowed disabled:opacity-40">
                                Transferencia
                            </button>
                            <button type="button" x-on:click="nuevoPago.metodo = 'QR'; pagoExacto()"
                                    x-bind:disabled="faltaPago === false"
                                    class="rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface disabled:cursor-not-allowed disabled:opacity-40">
                                QR
                            </button>
                        </div>

                        <template x-if="pagos.length === 0">
                            <p class="mt-4 font-body-sm text-body-sm text-on-surface-variant">
                                Todavía no registraste pagos.
                            </p>
                        </template>

                        <ul x-show="pagos.length > 0" x-cloak class="mt-4 space-y-2">
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

                        <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-outline-variant/15 pt-3 font-body-md text-body-md">
                            <div class="rounded-obsidian bg-surface-container-low/50 px-3 py-2">
                                <dt class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">Total pagado</dt>
                                <dd class="mt-1 font-label-lg text-label-lg tabular-nums text-on-surface" x-text="money(totalPagado)"></dd>
                            </div>
                            <div class="rounded-obsidian bg-surface-container-low/50 px-3 py-2">
                                <dt class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">Saldo pendiente</dt>
                                <dd class="mt-1 font-label-lg text-label-lg tabular-nums" x-text="money(saldoPendiente)"
                                    x-bind:class="faltaPago ? 'text-amber-400' : 'text-emerald-400'"></dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- Columna de cierre: total, descuento y confirmación --}}
                <aside class="lg:col-span-1">
                    <div class="space-y-6 lg:sticky lg:top-28">

                        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
                            <h3 class="font-headline-sm text-headline-sm text-on-surface">Resumen</h3>

                            <dl class="mt-3 space-y-2 font-body-md text-body-md">
                                <div class="flex items-center justify-between">
                                    <dt class="text-on-surface-variant">Subtotal</dt>
                                    <dd class="tabular-nums text-on-surface" x-text="money(subtotal)"></dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-on-surface-variant">Descuento</dt>
                                    <dd class="tabular-nums text-on-surface" x-text="'- ' + money(descuentoAplicado)"></dd>
                                </div>
                            </dl>

                            <div class="mt-4 flex items-baseline justify-between border-t border-outline-variant/15 pt-4">
                                <span class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</span>
                                <span class="font-headline-lg text-headline-lg tabular-nums text-on-surface" x-text="money(total)"></span>
                            </div>

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

                                <p x-show="descuentoExcedeLimite && ! autorizacionId" x-cloak
                                   class="mt-2 font-body-sm text-body-sm text-amber-400">
                                    Descuento requiere autorización de administrador.
                                </p>

                                {{-- HU-5.11: pedir autorización al admin sin salir del POS. --}}
                                <button type="button" x-show="descuentoExcedeLimite && ! autorizacionId" x-cloak
                                        x-on:click="abrirModalAutorizacion()"
                                        class="mt-2 w-full rounded-obsidian border border-amber-500/40 bg-amber-500/10 px-3 py-2 font-label-md text-label-md text-amber-400 transition hover:bg-amber-500/20">
                                    Solicitar autorización
                                </button>

                                <p x-show="autorizacionId" x-cloak class="mt-2 font-body-sm text-body-sm text-emerald-400">
                                    Descuento autorizado<span x-show="autorizacionAdmin" x-text="' por ' + autorizacionAdmin"></span>.
                                    Autorización registrada.
                                </p>
                            </div>
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
                                    x-bind:disabled="items.length === 0 || pagos.length === 0 || enviando || faltaPago || faltanSeries">
                                <span x-show="! enviando">Confirmar venta</span>
                                <span x-show="enviando" x-cloak>Confirmando…</span>
                            </button>

                            <p x-show="faltanSeries" x-cloak class="mt-2 font-body-sm text-body-sm text-error">
                                Elegí la serie de los productos marcados con 'Requiere serie'.
                            </p>

                            <button type="button" x-on:click="irAPaso(2)"
                                    class="w-full rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                                Volver al carrito
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        </section>
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
                                x-bind:disabled="serieModal.validando"
                                class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 text-left font-label-md text-label-md tabular-nums text-on-surface transition hover:border-primary-container/40 disabled:cursor-not-allowed disabled:opacity-40">
                            <span x-text="serie.numero_serie"></span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </div>

    {{-- Modal de autorización de descuento (HU-5.11) --}}
    <div x-show="modalAutorizacion.abierto" x-cloak
         x-on:keydown.escape.window="cerrarModalAutorizacion()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="w-full max-w-md rounded-obsidian-xl border border-outline-variant/30 bg-surface-container p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Venta rápida
                    </p>
                    <h3 class="mt-1 font-headline-sm text-headline-sm text-on-surface">
                        Autorizar descuento
                    </h3>
                </div>
                <button type="button" x-on:click="cerrarModalAutorizacion()" aria-label="Cerrar"
                        class="text-body-lg text-on-surface-variant transition hover:text-error">
                    ×
                </button>
            </div>

            <p class="mt-3 font-body-sm text-body-sm text-on-surface-variant">
                El descuento de
                <span class="tabular-nums text-on-surface" x-text="money(descuentoMonto)"></span>
                (<span class="tabular-nums" x-text="descuentoPorcentaje.toFixed(2) + ' %'"></span>)
                supera tu límite de <span x-text="descuentoMaximo + ' %'"></span>.
                Un administrador tiene que autorizarlo con sus credenciales.
            </p>

            <form class="mt-5 space-y-4" x-on:submit.prevent="solicitarAutorizacion()">
                <div>
                    <label for="autorizacion-email" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Email del administrador <span class="text-error">*</span>
                    </label>
                    <input id="autorizacion-email" type="email" x-model="modalAutorizacion.email"
                           autocomplete="off"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    <p x-show="modalAutorizacion.errores.email" x-cloak
                       class="mt-1 font-body-sm text-body-sm text-error"
                       x-text="modalAutorizacion.errores.email?.[0]"></p>
                </div>

                <div>
                    <label for="autorizacion-password" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Contraseña <span class="text-error">*</span>
                    </label>
                    <input id="autorizacion-password" type="password" x-model="modalAutorizacion.password"
                           autocomplete="off"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    <p x-show="modalAutorizacion.errores.password" x-cloak
                       class="mt-1 font-body-sm text-body-sm text-error"
                       x-text="modalAutorizacion.errores.password?.[0]"></p>
                </div>

                <div>
                    <label for="autorizacion-motivo" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Motivo (opcional)
                    </label>
                    <textarea id="autorizacion-motivo" rows="2" x-model="modalAutorizacion.motivo"
                              class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25"></textarea>
                    <p x-show="modalAutorizacion.errores.motivo" x-cloak
                       class="mt-1 font-body-sm text-body-sm text-error"
                       x-text="modalAutorizacion.errores.motivo?.[0]"></p>
                </div>

                <div x-show="modalAutorizacion.error" x-cloak
                     class="rounded-obsidian border border-error/40 bg-error/10 px-3 py-2 font-body-sm text-body-sm text-error"
                     x-text="modalAutorizacion.error"></div>

                <ul x-show="erroresCamposAutorizacion.length > 0" x-cloak
                    class="space-y-1 rounded-obsidian border border-error/40 bg-error/10 px-3 py-2 font-body-sm text-body-sm text-error">
                    <template x-for="mensaje in erroresCamposAutorizacion" :key="mensaje">
                        <li x-text="mensaje"></li>
                    </template>
                </ul>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" x-on:click="cerrarModalAutorizacion()"
                            class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2 text-body-md text-on-surface-variant transition hover:text-on-surface">
                        Cancelar
                    </button>
                    <button type="submit" x-bind:disabled="modalAutorizacion.enviando"
                            class="rounded-obsidian-lg bg-primary-container px-4 py-2 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90 disabled:cursor-not-allowed disabled:opacity-40">
                        <span x-show="! modalAutorizacion.enviando">Autorizar descuento</span>
                        <span x-show="modalAutorizacion.enviando" x-cloak>Autorizando…</span>
                    </button>
                </div>
            </form>
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

{{-- POS: el comportamiento vive en resources/js/pos.js (window.carrito). --}}
@endsection
