@extends('layouts.app')

@section('title', 'Factura '.$factura->numero_factura)
@section('subtitle', 'Comprobante electrónico (simulación)')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Factura</p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">{{ $factura->numero_factura }}</h2>
        </div>
        <span class="inline-flex rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em]
            {{ $factura->estaAnulada()
                ? 'border-error/25 bg-error/10 text-error'
                : 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400' }}">
            {{ $factura->estado }}
        </span>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <h3 class="mb-4 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Datos</h3>
            <dl class="space-y-3 font-body-md text-body-md">
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Venta</dt>
                    <dd>
                        <a href="{{ route('admin.ventas.show', $factura->venta_id) }}" class="text-secondary transition hover:text-on-surface">
                            #{{ str_pad((string) $factura->venta_id, 6, '0', STR_PAD_LEFT) }}
                        </a>
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Cliente</dt>
                    <dd class="text-on-surface">{{ $factura->venta?->cliente?->nombre ?? 'Consumidor final' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Monto</dt>
                    <dd class="tabular-nums text-on-surface">Bs {{ number_format((float) ($factura->venta?->total ?? 0), 2, '.', ',') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Emisión</dt>
                    <dd class="text-on-surface">{{ $factura->fecha_emision?->format('d/m/Y H:i') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-surface-variant">Emitida por</dt>
                    <dd class="text-on-surface">{{ $factura->user?->name ?? 'Sistema' }}</dd>
                </div>
                <div>
                    <dt class="text-on-surface-variant">CUF</dt>
                    <dd class="mt-1 break-all font-mono text-body-sm text-on-surface">{{ $factura->cuf }}</dd>
                </div>
                @if ($factura->estaAnulada())
                    <div>
                        <dt class="text-on-surface-variant">Motivo de anulación</dt>
                        <dd class="mt-1 text-error">{{ $factura->motivo_anulacion }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <h3 class="mb-4 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Detalle de la venta</h3>
            <table class="min-w-full text-body-sm">
                <thead>
                    <tr class="text-left text-outline">
                        <th class="pb-2">Producto</th>
                        <th class="pb-2 text-right">Cant.</th>
                        <th class="pb-2 text-right">Precio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($factura->venta?->detalles ?? [] as $detalle)
                        <tr class="border-t border-outline-variant/15">
                            <td class="py-2 text-on-surface">{{ $detalle->producto?->nombre ?? 'Producto' }}</td>
                            <td class="py-2 text-right tabular-nums text-on-surface-variant">{{ $detalle->cantidad }}</td>
                            <td class="py-2 text-right tabular-nums text-on-surface-variant">
                                Bs {{ number_format((float) $detalle->precio_unitario, 2, '.', ',') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($factura->xml)
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <h3 class="mb-2 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">XML (simulación)</h3>
            <pre class="overflow-x-auto rounded-obsidian bg-surface-container-low p-3 font-mono text-body-sm text-on-surface-variant">{{ $factura->xml }}</pre>
        </div>
    @endif

    @can('anular facturacion')
        @unless ($factura->estaAnulada())
            <form method="POST" action="{{ route('admin.facturacion.anular', $factura) }}"
                  class="flex flex-wrap items-end gap-3 rounded-obsidian-xl border border-error/25 bg-error/5 p-5"
                  onsubmit="return confirm('¿Anular la factura {{ $factura->numero_factura }}?')">
                @csrf
                <div>
                    <label for="motivo" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Motivo</label>
                    <select name="motivo" id="motivo" required
                            class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                        <option value="">Seleccione</option>
                        @foreach (['Error de datos', 'Devolución total', 'Duplicada', 'Otro'] as $motivo)
                            <option value="{{ $motivo }}">{{ $motivo }}</option>
                        @endforeach
                    </select>
                    @error('motivo')
                        <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="rounded-obsidian bg-error/10 px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-error transition hover:bg-error/20">
                    Anular factura
                </button>
            </form>
        @endunless
    @endcan

    <a href="{{ route('admin.facturacion.index') }}" class="inline-block font-body-sm text-body-sm text-secondary transition hover:text-on-surface">
        ← Volver al listado
    </a>
</div>
@endsection
