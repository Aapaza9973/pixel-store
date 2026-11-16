@extends('layouts.app')

@section('title', 'Facturación')
@section('subtitle', 'Facturas electrónicas (modo simulación)')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-end justify-between gap-4 rounded-obsidian-xl border border-amber-500/25 bg-amber-500/10 p-4">
        <p class="font-body-sm text-body-sm text-amber-200">
            Modo simulación: el número y el CUF se generan localmente y el XML no se envía al SIN.
            Falta configurar el proveedor (CUCU / Libélula) para emitir con validez tributaria.
        </p>
    </div>

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <form method="GET" action="{{ route('admin.facturacion.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="estado" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</label>
                <select name="estado" id="estado"
                        class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    <option value="">Todas</option>
                    @foreach ($estados as $opcion)
                        <option value="{{ $opcion }}" {{ $estado === $opcion ? 'selected' : '' }}>{{ ucfirst($opcion) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">Filtrar</button>
                <a href="{{ route('admin.facturacion.index') }}" class="rounded-obsidian bg-surface-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">Limpiar</a>
            </div>
        </form>
    </div>

    @can('emitir facturacion')
        <form method="POST" action="{{ route('admin.facturacion.store') }}"
              class="flex flex-wrap items-end gap-3 rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            @csrf
            <div>
                <label for="venta_id" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Emitir para venta N°</label>
                <input type="number" min="1" name="venta_id" id="venta_id" value="{{ old('venta_id') }}" required
                       class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                @error('venta_id')
                    <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">
                Emitir factura
            </button>
        </form>
    @endcan

    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Número</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Venta</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cliente</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Monto</th>
                        <th class="px-4 py-3.5 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Emisión</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($facturas as $factura)
                        <tr class="border-b border-outline-variant/15 transition hover:bg-primary-container/[0.04] last:border-0">
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface">{{ $factura->numero_factura }}</td>
                            <td class="px-4 py-3.5 text-on-surface">#{{ str_pad((string) $factura->venta_id, 6, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-4 py-3.5 text-on-surface-variant">{{ $factura->venta?->cliente?->nombre ?? 'Consumidor final' }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">Bs {{ number_format((float) ($factura->venta?->total ?? 0), 2, '.', ',') }}</td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em]
                                    {{ $factura->estaAnulada()
                                        ? 'border-error/25 bg-error/10 text-error'
                                        : 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400' }}">
                                    {{ $factura->estado }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface-variant">{{ $factura->fecha_emision?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('admin.facturacion.show', $factura) }}"
                                   class="font-body-sm text-body-sm text-secondary transition hover:text-on-surface">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center font-body-md text-body-md text-outline">No hay facturas emitidas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($facturas->hasPages())
            <div class="mt-4 px-5 pb-5">{{ $facturas->links() }}</div>
        @endif
    </div>
</div>
@endsection
