@extends('layouts.app')

@section('title', 'Pedido web')
@section('subtitle', 'Ventas del catálogo público')

@section('content')
@php
    $numero = '#'.str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT);

    $claseEstado = match ($venta->estado) {
        'Pendiente' => 'border-amber-500/25 bg-amber-500/10 text-amber-400',
        'Pagado' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
        default => 'border-outline-variant/30 bg-surface-container-low text-outline',
    };
@endphp

<div class="space-y-6" x-data="{ cancelarAbierto: false }">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Pedido web</p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">{{ $numero }}</h2>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                {{ $venta->fecha?->format('d/m/Y H:i') }} · origen web
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-1.5 rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] {{ $claseEstado }}">
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                {{ $venta->estado }}
            </span>

            @if ($venta->estado === 'Pendiente')
                @can('editar ventas')
                    <form method="POST" action="{{ route('admin.pedidos-web.pagar', $venta) }}"
                          onsubmit="return confirm('¿Marcar el pedido {{ $numero }} como pagado? Se registrará el ingreso.')">
                        @csrf
                        <button type="submit"
                                class="rounded-obsidian-lg bg-primary-container px-4 py-2 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                            Marcar como pagado
                        </button>
                    </form>
                @endcan

                @can('cancelar ventas')
                    <button type="button" x-on:click="cancelarAbierto = true"
                            class="rounded-obsidian-lg border border-error/50 px-4 py-2 text-body-md font-medium text-error transition hover:bg-error/10">
                        Cancelar pedido
                    </button>
                @endcan
            @endif

            <a href="{{ route('admin.pedidos-web.index') }}"
               class="text-body-sm text-on-surface-variant transition hover:text-on-surface">
                Volver al listado
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]">

        {{-- Líneas --}}
        <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
            <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Productos</h3>
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
                                Bs {{ number_format((float) $detalle->precio_unitario, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">
                                Bs {{ number_format((float) $detalle->precio_unitario * $detalle->cantidad, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-surface-container-low">
                    <tr>
                        <th colspan="3" class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                        <th class="px-4 py-3.5 text-right font-label-md text-label-md tabular-nums text-on-surface">
                            Bs {{ number_format((float) $venta->total, 2, ',', '.') }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Contacto --}}
        <aside class="self-start space-y-4 rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <div>
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Contacto</p>
                <p class="mt-1.5 font-body-md text-body-md text-on-surface">{{ $venta->cliente?->nombre ?? 'Sin cliente' }}</p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">NIT/CI {{ $venta->cliente?->nit_ci ?? '—' }}</p>
            </div>

            <div class="space-y-1.5 border-t border-outline-variant/20 pt-4">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Email</p>
                @if ($venta->cliente?->email)
                    <a href="mailto:{{ $venta->cliente->email }}" class="font-body-sm text-body-sm text-secondary transition hover:text-on-surface">
                        {{ $venta->cliente->email }}
                    </a>
                @else
                    <p class="font-body-sm text-body-sm text-outline">—</p>
                @endif
            </div>

            <div class="space-y-1.5 border-t border-outline-variant/20 pt-4">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Teléfono</p>
                @if ($venta->cliente?->telefono)
                    <a href="tel:{{ $venta->cliente->telefono }}" class="font-body-sm text-body-sm text-secondary transition hover:text-on-surface">
                        {{ $venta->cliente->telefono }}
                    </a>
                @else
                    <p class="font-body-sm text-body-sm text-outline">—</p>
                @endif
            </div>

            <div class="space-y-1.5 border-t border-outline-variant/20 pt-4">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Dirección de entrega</p>
                <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $venta->cliente?->direccion ?? '—' }}</p>
            </div>
        </aside>
    </div>

    {{-- Modal de cancelación --}}
    <div x-cloak x-show="cancelarAbierto" x-on:keydown.escape.window="cancelarAbierto = false"
         class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="cancelar-titulo">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" x-on:click="cancelarAbierto = false"></div>

        <div class="absolute left-1/2 top-1/2 w-[calc(100%-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-obsidian-xl border border-outline-variant/40 bg-surface-container-high p-6 shadow-2xl">
            <h3 id="cancelar-titulo" class="font-headline-sm text-headline-sm text-on-surface">
                Cancelar pedido {{ $numero }}
            </h3>

            <p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">
                El pedido pasará a <span class="text-on-surface">Cancelada</span> y se reingresará el stock reservado.
                Esta acción no se puede deshacer.
            </p>

            <form method="POST" action="{{ route('admin.pedidos-web.cancelar', $venta) }}" class="mt-5 flex justify-end gap-3">
                @csrf

                <button type="button" x-on:click="cancelarAbierto = false"
                        class="rounded-obsidian-lg border border-outline-variant/40 px-4 py-2 text-body-md text-on-surface-variant transition hover:text-on-surface">
                    Volver
                </button>

                <button type="submit"
                        class="rounded-obsidian-lg bg-error/90 px-4 py-2 text-body-md font-medium text-white transition hover:bg-error">
                    Cancelar pedido
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
