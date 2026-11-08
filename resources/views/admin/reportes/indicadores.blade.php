@extends('layouts.app')

@section('title', 'Indicadores')
@section('subtitle', 'Utilidad neta, ranking de productos y antigüedad de inventario')

@section('content')
@php
    $dinero = fn (float $valor): string => 'Bs '.number_format($valor, 2, '.', ',');
@endphp

<div class="space-y-6">

    {{-- Filtros --}}
    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <form method="GET" action="{{ route('admin.reportes.indicadores') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="desde" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Desde</label>
                <input type="date" name="desde" id="desde" value="{{ $desde }}"
                       class="rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md text-on-surface">
            </div>
            <div>
                <label for="hasta" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Hasta</label>
                <input type="date" name="hasta" id="hasta" value="{{ $hasta }}"
                       class="rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md text-on-surface">
            </div>
            <button type="submit"
                    class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">
                Generar
            </button>
            @can('ver reportes')
                <a href="{{ route('admin.reportes.pdf', ['tipo' => 'indicadores', 'desde' => $desde, 'hasta' => $hasta]) }}"
                   class="rounded-obsidian border border-outline-variant/40 bg-surface-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                    Exportar PDF
                </a>
                <a href="{{ route('admin.reportes.excel', ['tipo' => 'indicadores', 'desde' => $desde, 'hasta' => $hasta]) }}"
                   class="ml-2 rounded-obsidian border border-outline-variant/40 bg-surface-container px-3 py-1.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                    Exportar Excel
                </a>
            @endcan
        </form>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ingresos</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-on-surface">{{ $dinero($ingresos) }}</p>
        </article>
        <article class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Costo histórico</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-on-surface">
                {{ $costo === null ? '—' : $dinero($costo) }}
            </p>
        </article>
        <article class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Utilidad neta</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums {{ $utilidadTotal !== null && $utilidadTotal < 0 ? 'text-error' : 'text-emerald-400' }}">
                {{ $utilidadTotal === null ? '—' : $dinero($utilidadTotal) }}
            </p>
        </article>
        <article class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Margen</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-on-surface">
                {{ $margen === null ? '—' : number_format($margen, 1, ',', '.').' %' }}
            </p>
        </article>
    </div>

    @unless ($hayCosto)
        <x-alert type="warning"
                 message="Sin datos de costo histórico en el rango: las ventas anteriores a la captura de precio_costo no permiten calcular utilidad." />
    @endunless

    {{-- Utilidad neta por producto --}}
    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Utilidad neta por producto</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cantidad</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ingresos</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Costo</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Utilidad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($utilidad as $fila)
                        <tr class="border-b border-outline-variant/15 last:border-0 hover:bg-primary-container/[0.04]">
                            <td class="px-4 py-3.5 text-on-surface">{{ $fila['nombre'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">{{ $fila['cantidad'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">{{ $dinero((float) $fila['ingresos']) }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">
                                {{ $fila['costo'] === null ? '—' : $dinero((float) $fila['costo']) }}
                            </td>
                            <td class="px-4 py-3.5 text-right tabular-nums {{ $fila['utilidad'] !== null && $fila['utilidad'] < 0 ? 'text-error' : 'text-emerald-400' }}">
                                {{ $fila['utilidad'] === null ? '—' : $dinero((float) $fila['utilidad']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center font-body-md text-body-md text-outline">No hay ventas cobradas en el rango.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Ranking top 10 --}}
        <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
            <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Top 10 productos</h3>
            </div>
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">#</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cantidad</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ranking as $indice => $fila)
                        <tr class="border-b border-outline-variant/15 last:border-0 hover:bg-primary-container/[0.04]">
                            <td class="px-4 py-3.5 font-label-md text-label-md tabular-nums text-outline">{{ $indice + 1 }}</td>
                            <td class="px-4 py-3.5 text-on-surface">{{ $fila['nombre'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">{{ $fila['cantidad'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">{{ $dinero((float) $fila['total']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center font-body-md text-body-md text-outline">No hay ventas cobradas en el rango.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Productos menos vendidos --}}
        <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
            <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Productos menos vendidos</h3>
                <p class="mt-0.5 font-body-sm text-body-sm text-on-surface-variant">Top 10 con menor cantidad vendida dentro del período</p>
            </div>
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cantidad</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($menosVendidos as $fila)
                        <tr class="border-b border-outline-variant/15 last:border-0">
                            <td class="px-4 py-3.5 text-on-surface">{{ $fila['nombre'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">{{ $fila['cantidad'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">{{ $dinero((float) $fila['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-12 text-center text-outline">No hay ventas en el período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Antigüedad de inventario --}}
        <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
            <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Antigüedad de inventario</h3>
                <p class="mt-0.5 font-body-sm text-body-sm text-on-surface-variant">Días desde la última entrada registrada · productos con stock</p>
            </div>
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Producto</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Stock</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Días desde entrada</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($antiguedad as $fila)
                        <tr class="border-b border-outline-variant/15 last:border-0 hover:bg-primary-container/[0.04]">
                            <td class="px-4 py-3.5 text-on-surface">{{ $fila['nombre'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">{{ $fila['stock'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums {{ $fila['dias_desde_entrada'] === null ? 'text-outline' : 'text-on-surface' }}">
                                {{ $fila['dias_desde_entrada'] === null ? 'Sin entrada registrada' : $fila['dias_desde_entrada'].' d' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-12 text-center font-body-md text-body-md text-outline">No hay productos con stock.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
