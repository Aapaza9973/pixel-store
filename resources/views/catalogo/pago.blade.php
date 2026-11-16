@extends('layouts.app')

@section('title', 'Pagar pedido')
@section('subtitle', 'Catálogo público')

@section('content')
@php
    $numero = str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT);
@endphp

<div class="mx-auto max-w-2xl space-y-6">

    <div class="rounded-obsidian-xl border border-amber-500/25 bg-amber-500/10 p-4">
        <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-amber-300">Modo simulación</p>
        <p class="mt-1 font-body-sm text-body-sm text-amber-200/90">
            Esto no cobra dinero real: es una pasarela de prueba para validar el flujo.
            La integración con Stripe/PayPal queda pendiente de credenciales.
        </p>
    </div>

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
        <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Pedido</p>
        <h1 class="mt-1 font-headline-md text-headline-md text-on-surface">#{{ $numero }}</h1>

        <p class="mt-3 font-body-md text-body-md text-on-surface-variant">
            Total a pagar:
            <span class="font-headline-sm text-headline-sm tabular-nums text-on-surface">
                Bs {{ number_format((float) $venta->total, 2, ',', '.') }}
            </span>
        </p>

        <ul class="mt-4 space-y-1 font-body-sm text-body-sm text-on-surface-variant">
            @foreach ($venta->detalles as $detalle)
                <li class="flex justify-between gap-4">
                    <span>{{ $detalle->producto?->nombre ?? 'Producto' }} × {{ $detalle->cantidad }}</span>
                    <span class="tabular-nums">Bs {{ number_format((float) $detalle->precio_unitario * $detalle->cantidad, 2, ',', '.') }}</span>
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('pedido.pagar', ['venta' => $venta->id, 'token' => $token]) }}" class="mt-6">
            @csrf
            <button type="submit"
                    class="w-full rounded-obsidian-lg bg-primary-container px-4 py-3 font-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                Pagar Bs {{ number_format((float) $venta->total, 2, ',', '.') }} (simulado)
            </button>
        </form>
    </div>

    <a href="{{ route('catalogo.index') }}" class="inline-block font-body-sm text-body-sm text-secondary transition hover:text-on-surface">
        ← Volver al catálogo
    </a>
</div>
@endsection
