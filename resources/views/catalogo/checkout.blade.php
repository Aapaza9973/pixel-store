@extends('layouts.app')

@section('title', 'Finalizar compra')
@section('subtitle', 'Catálogo público')

@section('content')
@php
    $inputClass = 'mt-1.5 w-full rounded-obsidian border border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-sm text-body-sm text-on-surface transition focus:border-primary-container focus:outline-none focus:ring-2 focus:ring-primary-container/25';
    $labelClass = 'font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline';
@endphp

<div class="mx-auto max-w-5xl space-y-6">

    <nav class="flex items-center gap-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
        <a href="{{ url('/') }}" class="transition hover:text-on-surface">Inicio</a>
        <span>/</span>
        <a href="{{ route('catalogo.index') }}" class="transition hover:text-on-surface">Catálogo</a>
        <span>/</span>
        <span class="text-on-surface">Checkout</span>
    </nav>

    <header class="border-b border-outline-variant/30 pb-4">
        <p class="{{ $labelClass }}">Pixel Store</p>
        <h1 class="mt-1 font-headline-lg text-headline-lg text-on-surface">Finalizar compra</h1>
        <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
            Dejanos tus datos y coordinamos la entrega. El pago se realiza al recibir el pedido.
        </p>
    </header>

    {{-- Carrito vacío --}}
    <template x-if="items.length === 0">
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-10 text-center backdrop-blur-xl">
            <p class="font-headline-sm text-headline-sm text-on-surface">Tu carrito está vacío.</p>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                Agregá productos del catálogo para iniciar un pedido.
            </p>
            <a href="{{ route('catalogo.index') }}"
               class="mt-4 inline-flex items-center rounded-obsidian-lg bg-primary-container px-4 py-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-on-primary-container transition hover:bg-primary-container/90">
                Ver catálogo
            </a>
        </div>
    </template>

    <form method="POST" action="{{ route('catalogo.checkout.store') }}"
          x-show="items.length > 0" x-cloak
          x-on:submit.prevent="confirmarPedido($el)"
          class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]">
        @csrf

        {{-- Datos de entrega --}}
        <div class="space-y-4 rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Datos de entrega</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="nombre" class="{{ $labelClass }}">Nombre completo</label>
                    <input id="nombre" name="nombre" type="text" required maxlength="255"
                           value="{{ old('nombre') }}" class="{{ $inputClass }}">
                    @error('nombre')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="nit_ci" class="{{ $labelClass }}">NIT / CI</label>
                    <input id="nit_ci" name="nit_ci" type="text" required maxlength="20"
                           value="{{ old('nit_ci') }}" class="{{ $inputClass }}">
                    @error('nit_ci')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="telefono" class="{{ $labelClass }}">Teléfono</label>
                    <input id="telefono" name="telefono" type="tel" required minlength="7" maxlength="30"
                           value="{{ old('telefono') }}" class="{{ $inputClass }}">
                    @error('telefono')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="email" class="{{ $labelClass }}">Correo electrónico</label>
                    <input id="email" name="email" type="email" required maxlength="255"
                           value="{{ old('email') }}" class="{{ $inputClass }}">
                    @error('email')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="direccion" class="{{ $labelClass }}">Dirección de entrega</label>
                    <input id="direccion" name="direccion" type="text" required maxlength="255"
                           value="{{ old('direccion') }}" class="{{ $inputClass }}">
                    @error('direccion')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            @if (session('error'))
                <p class="rounded-obsidian border border-error/40 bg-error/10 px-3 py-2 font-body-sm text-body-sm text-error">
                    {{ session('error') }}
                </p>
            @endif
        </div>

        {{-- Resumen --}}
        <aside class="space-y-4 self-start rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl lg:sticky lg:top-0">
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Resumen del pedido</h2>

            <ul class="space-y-2 border-b border-outline-variant/20 pb-3">
                <template x-for="item in items" :key="item.producto_id">
                    <li class="flex items-start justify-between gap-3">
                        <span class="font-body-sm text-body-sm text-on-surface-variant">
                            <span x-text="item.nombre"></span>
                            <span class="text-outline" x-text="` × ${item.cantidad}`"></span>
                        </span>
                        <span class="font-label-md text-label-md tabular-nums text-on-surface"
                              x-text="formato(item.precio * item.cantidad)"></span>
                    </li>
                </template>
            </ul>

            <div class="flex items-baseline justify-between">
                <span class="{{ $labelClass }}">Total</span>
                <span class="font-headline-sm text-headline-sm tabular-nums text-on-surface" x-text="formato(subtotal)"></span>
            </div>

            <p x-show="checkoutError" x-cloak x-text="checkoutError"
               class="rounded-obsidian border border-error/40 bg-error/10 px-3 py-2 font-body-sm text-body-sm text-error"></p>

            <button type="submit" x-bind:disabled="procesando"
                    class="flex w-full items-center justify-center rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90 disabled:cursor-not-allowed disabled:opacity-60">
                <span x-show="! procesando">Confirmar pedido</span>
                <span x-show="procesando" x-cloak>Enviando…</span>
            </button>

            <a href="{{ route('catalogo.index') }}"
               class="block text-center font-label-sm text-label-sm uppercase tracking-[0.14em] text-secondary transition hover:text-on-surface">
                Seguir comprando
            </a>
        </aside>
    </form>
</div>
@endsection
