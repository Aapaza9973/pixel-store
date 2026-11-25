@extends('layouts.public')

@section('robots', 'noindex, nofollow')

@section('title', 'Pedido recibido')

@section('content')
@php
    $numero = str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT);
@endphp

<div class="mx-auto max-w-3xl space-y-6">

    <nav class="flex items-center gap-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
        <a href="{{ route('home') }}" class="transition hover:text-on-surface">Inicio</a>
        <span>/</span>
        <a href="{{ route('catalogo.index') }}" class="transition hover:text-on-surface">Catálogo</a>
        <span>/</span>
        <span class="text-on-surface">Pedido #{{ $numero }}</span>
    </nav>

    <div class="rounded-obsidian-xl border border-emerald-500/25 bg-emerald-500/10 p-6 text-center">
        <span class="inline-flex items-center gap-1.5 rounded-obsidian border border-emerald-500/25 bg-emerald-500/10 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-emerald-400">
            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
            Pedido recibido
        </span>

        <h1 class="mt-3 font-headline-lg text-headline-lg text-on-surface">
            Pedido #{{ $numero }} recibido
        </h1>

        <p class="mx-auto mt-2 max-w-lg font-body-sm text-body-sm text-on-surface-variant">
            Reservamos el stock y dejamos el pedido en estado Pendiente. Te contactamos
            @if ($venta->cliente?->telefono)
                al {{ $venta->cliente->telefono }}
            @endif
            para coordinar la entrega y el pago.
        </p>
    </div>

    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Resumen del pedido</h2>
        </div>

        <table class="min-w-full text-body-md">
            <thead class="bg-surface-container-low">
                <tr class="text-left">
                    <th class="px-4 py-3 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                    <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cantidad</th>
                    <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Precio</th>
                    <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venta->detalles as $detalle)
                    <tr class="border-b border-outline-variant/15 last:border-0">
                        <td class="px-4 py-3.5 text-on-surface">{{ $detalle->producto?->nombre ?? 'Producto eliminado' }}</td>
                        <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">{{ $detalle->cantidad }}</td>
                        <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">
                            {{ \App\Support\Dinero::formatear((float) $detalle->precio_unitario) }}
                        </td>
                        <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">
                            {{ \App\Support\Dinero::formatear((float) $detalle->precio_unitario * $detalle->cantidad) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-surface-container-low">
                <tr>
                    <th colspan="3" class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                    <th class="px-4 py-3.5 text-right font-label-md text-label-md tabular-nums text-on-surface">
                        {{ \App\Support\Dinero::formatear((float) $venta->total) }}
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Entrega</p>
            <p class="mt-1.5 font-body-md text-body-md text-on-surface">{{ $venta->cliente?->nombre ?? 'Sin nombre registrado' }}</p>
            @if ($venta->cliente?->nit_ci)
                <p class="font-body-sm text-body-sm text-on-surface-variant">NIT/CI {{ $venta->cliente->nit_ci }}</p>
            @endif
            @if ($venta->cliente?->direccion)
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ $venta->cliente->direccion }}</p>
            @endif
        </div>

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</p>
            <p class="mt-1.5 font-body-md text-body-md text-on-surface">{{ $venta->estado }}</p>
            <p class="font-body-sm text-body-sm text-on-surface-variant">
                Pedido web del {{ $venta->fecha?->format('d/m/Y H:i') }} · pago al entregar
            </p>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('catalogo.index') }}"
           class="inline-flex items-center rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-body-md text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
            Volver al catálogo
        </a>
        <a href="{{ route('catalogo.favoritos.index') }}"
           class="inline-flex items-center rounded-obsidian-lg border border-outline-variant/40 px-4 py-2.5 font-body-md text-body-md font-medium text-on-surface-variant transition hover:border-primary-container/40 hover:text-on-surface">
            Ver mis favoritos
        </a>
    </div>
</div>
@endsection
