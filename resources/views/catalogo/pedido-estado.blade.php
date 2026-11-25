@extends('layouts.public')

@section('robots', 'noindex, nofollow')

@section('title', 'Estado del pedido')

@section('content')
@php
    $numero = str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT);
    $estilos = match ($accion) {
        'confirmado' => ['border-emerald-500/25 bg-emerald-500/10 text-emerald-400', 'Pedido confirmado'],
        'cancelado' => ['border-amber-500/25 bg-amber-500/10 text-amber-400', 'Pedido cancelado'],
        default => ['border-outline-variant/30 bg-surface-container-low text-on-surface-variant', 'Sin cambios'],
    };
@endphp

<div class="mx-auto max-w-2xl space-y-6">
    <div class="rounded-obsidian-xl border p-6 text-center {{ $estilos[0] }}">
        <span class="inline-flex items-center gap-1.5 rounded-obsidian border border-current/30 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em]">
            {{ $estilos[1] }}
        </span>
        <h1 class="mt-3 font-headline-md text-headline-md text-on-surface">Pedido #{{ $numero }}</h1>
        <p class="mt-2 font-body-md text-body-md text-on-surface-variant">{{ $mensaje }}</p>
    </div>

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <dl class="space-y-2 font-body-md text-body-md">
            <div class="flex justify-between gap-4">
                <dt class="text-on-surface-variant">Estado</dt>
                <dd class="text-on-surface">{{ $venta->estado }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-on-surface-variant">Total</dt>
                <dd class="tabular-nums text-on-surface">{{ \App\Support\Dinero::formatear((float) $venta->total) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-on-surface-variant">Fecha</dt>
                <dd class="text-on-surface">{{ $venta->fecha?->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>

    <a href="{{ route('catalogo.index') }}"
       class="inline-flex items-center rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
        Volver al catálogo
    </a>
</div>
@endsection
