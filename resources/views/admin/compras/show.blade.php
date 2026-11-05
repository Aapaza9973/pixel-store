@extends('layouts.app')

@section('title', 'Orden '.$orden->numero)
@section('subtitle', 'Detalle de orden de compra')

@section('content')
<div class="space-y-6" x-data="{ recepcionAbierta: false }">

    @if (session('success'))
        <x-alert type="success" :message="session('success')" />
    @endif

    @if (session('error'))
        <x-alert type="error" :message="session('error')" />
    @endif

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Orden de compra</p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">{{ $orden->numero }}</h2>

            @php
                $colorEstado = match ($orden->estado) {
                    'borrador' => 'bg-surface-container-low text-on-surface-variant border-outline-variant/30',
                    'enviada' => 'bg-primary-container/10 text-secondary border-primary-container/25',
                    'recibida_parcial' => 'bg-amber-500/10 text-amber-400 border-amber-500/25',
                    'recibida_total' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25',
                    'cancelada' => 'bg-error/10 text-error border-error/25',
                    default => 'bg-surface-container-low text-on-surface-variant border-outline-variant/30',
                };
            @endphp

            <span class="mt-2 inline-flex rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] {{ $colorEstado }}">
                {{ ucfirst(str_replace('_', ' ', $orden->estado)) }}
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.compras.pdf', $orden) }}"
               class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container px-4 py-2 font-body-md text-body-md text-on-surface-variant transition hover:text-on-surface">
                Descargar PDF
            </a>

            @if ($puedeRecibir)
                <button type="button" x-on:click="recepcionAbierta = true"
                        class="rounded-obsidian-lg bg-primary-container px-4 py-2 font-body-md text-body-md text-on-primary-container transition hover:bg-primary-container/90">
                    Registrar recepción
                </button>
            @endif

            <a href="{{ route('admin.compras.index') }}"
               class="rounded-obsidian-lg bg-surface-container px-4 py-2 font-body-md text-body-md text-on-surface-variant transition hover:text-on-surface">
                Volver
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <h3 class="mb-4 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Proveedor</h3>
            <p class="font-headline-sm text-headline-sm text-on-surface">{{ $orden->proveedor?->nombre ?? 'Proveedor eliminado' }}</p>
            @if ($orden->proveedor?->nit)
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">NIT: {{ $orden->proveedor->nit }}</p>
            @endif
            @if ($orden->proveedor?->contacto)
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">Contacto: {{ $orden->proveedor->contacto }}</p>
            @endif
            @if ($orden->proveedor?->telefono)
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">Tel.: {{ $orden->proveedor->telefono }}</p>
            @endif
            @if ($orden->proveedor?->email)
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ $orden->proveedor->email }}</p>
            @endif
        </div>

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <h3 class="mb-4 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Datos de la orden</h3>
            <dl class="space-y-2 font-body-md text-body-md">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-on-surface-variant">Emisión</dt>
                    <dd class="text-on-surface">{{ ($orden->fecha_emision ?? $orden->fecha)?->format('d/m/Y') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-on-surface-variant">Entrega</dt>
                    <dd class="text-on-surface">{{ $orden->fecha_entrega?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-on-surface-variant">Elaborada por</dt>
                    <dd class="text-on-surface">{{ $orden->usuario?->name ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-on-surface-variant">Ítems</dt>
                    <dd class="text-on-surface">{{ $orden->detalles->count() }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="border-b border-outline-variant/20 px-5 py-4">
            <h3 class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ítems</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                        <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Pedido</th>
                        <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Recibido</th>
                        <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Pendiente</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Precio unit.</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15">
                    @forelse ($lineas as $linea)
                        <tr class="transition hover:bg-primary-container/[0.04]">
                            <td class="px-4 py-3.5 text-on-surface">{{ $linea['detalle']->producto?->nombre ?? 'Producto eliminado' }}</td>
                            <td class="px-4 py-3.5 text-center tabular-nums text-on-surface">{{ $linea['detalle']->cantidad }}</td>
                            <td class="px-4 py-3.5 text-center tabular-nums text-on-surface-variant">{{ $linea['recibido'] }}</td>
                            <td class="px-4 py-3.5 text-center tabular-nums {{ $linea['pendiente'] > 0 ? 'text-amber-400' : 'text-emerald-400' }}">
                                {{ $linea['pendiente'] }}
                            </td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">
                                Bs {{ number_format((float) $linea['detalle']->precio_unitario, 2, '.', ',') }}
                            </td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">
                                Bs {{ number_format((float) $linea['detalle']->subtotal, 2, '.', ',') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center font-body-md text-body-md text-outline">
                                La orden no tiene ítems.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex justify-end border-t border-outline-variant/20 px-5 py-4">
            <dl class="w-full max-w-xs space-y-2 font-body-md text-body-md">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-on-surface-variant">Subtotal</dt>
                    <dd class="tabular-nums text-on-surface">Bs {{ number_format((float) $orden->subtotal, 2, '.', ',') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-on-surface-variant">Impuestos</dt>
                    <dd class="tabular-nums text-on-surface">Bs {{ number_format((float) $orden->impuestos, 2, '.', ',') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-outline-variant/20 pt-2">
                    <dt class="font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface">Total</dt>
                    <dd class="font-headline-sm text-headline-sm tabular-nums text-on-surface">Bs {{ number_format((float) $orden->total, 2, '.', ',') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    @if ($orden->notas)
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <h3 class="mb-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Notas</h3>
            <p class="whitespace-pre-line font-body-md text-body-md text-on-surface-variant">{{ $orden->notas }}</p>
        </div>
    @endif

    {{-- Modal de recepción (3.4.7): solo para órdenes enviadas o parcialmente recibidas. --}}
    @if ($puedeRecibir)
        <div x-show="recepcionAbierta" x-cloak
             x-on:keydown.escape.window="recepcionAbierta = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-2xl rounded-obsidian-xl border border-outline-variant/30 bg-surface-container p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Recepción</p>
                        <h3 class="mt-1 font-headline-sm text-headline-sm text-on-surface">Registrar recepción</h3>
                        <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                            Indicá cuántas unidades llegaron de cada ítem. Solo se suman las cantidades mayores que cero.
                        </p>
                    </div>
                    <button type="button" x-on:click="recepcionAbierta = false" aria-label="Cerrar"
                            class="text-body-lg text-on-surface-variant transition hover:text-error">×</button>
                </div>

                <form method="POST" action="{{ route('admin.compras.recibir', $orden) }}" class="mt-5 space-y-4">
                    @csrf

                    <div class="max-h-72 space-y-2 overflow-y-auto">
                        @foreach ($lineas as $indice => $linea)
                            <div class="flex items-center justify-between gap-4 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2">
                                <div>
                                    <p class="font-body-md text-body-md text-on-surface">{{ $linea['detalle']->producto?->nombre ?? 'Producto eliminado' }}</p>
                                    <p class="font-body-sm text-body-sm text-on-surface-variant">
                                        Pedido: {{ $linea['detalle']->cantidad }} · Recibido: {{ $linea['recibido'] }} · Pendiente: {{ $linea['pendiente'] }}
                                    </p>
                                </div>
                                <input type="hidden" name="items[{{ $indice }}][orden_compra_item_id]" value="{{ $linea['detalle']->id }}">
                                <input type="number" min="0" max="{{ $linea['pendiente'] }}" value="0"
                                       name="items[{{ $indice }}][cantidad]"
                                       aria-label="Cantidad recibida de {{ $linea['detalle']->producto?->nombre ?? 'producto' }}"
                                       {{ $linea['pendiente'] === 0 ? 'disabled' : '' }}
                                       class="w-24 rounded-obsidian border-outline-variant/40 bg-surface-container px-2 py-1.5 text-center font-label-md text-label-md tabular-nums text-on-surface disabled:opacity-40">
                            </div>
                        @endforeach
                    </div>

                    <div>
                        <label for="recepcion-notas" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Notas</label>
                        <textarea name="notas" id="recepcion-notas" rows="2"
                                  class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface"></textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" x-on:click="recepcionAbierta = false"
                                class="rounded-obsidian-lg bg-surface-container px-4 py-2 font-body-md text-body-md text-on-surface-variant transition hover:text-on-surface">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-obsidian-lg bg-primary-container px-4 py-2 font-body-md text-body-md text-on-primary-container transition hover:bg-primary-container/90">
                            Registrar recepción
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
