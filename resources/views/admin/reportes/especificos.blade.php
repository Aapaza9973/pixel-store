@extends('layouts.app')

@section('title', 'Reportes específicos')
@section('subtitle', 'Ventas por vendedor, categoría y métodos de pago')

@section('content')
@php
    $tabs = [
        'vendedor' => ['label' => 'Ventas por vendedor', 'route' => 'admin.reportes.vendedor'],
        'categoria' => ['label' => 'Ventas por categoría', 'route' => 'admin.reportes.categoria'],
        'metodos' => ['label' => 'Métodos de pago', 'route' => 'admin.reportes.metodos'],
    ];
    $columna = match ($tipo) {
        'vendedor' => 'Vendedor',
        'categoria' => 'Categoría',
        default => 'Método',
    };
    $clave = match ($tipo) {
        'vendedor' => 'nombre',
        'categoria' => 'categoria',
        default => 'metodo',
    };
@endphp

<div class="space-y-6">

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <form method="GET" action="{{ route($tabs[$tipo]['route']) }}" class="flex flex-wrap items-end gap-3">
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
        </form>
    </div>

    <div class="flex gap-2">
        @foreach ($tabs as $claveTab => $tab)
            <a href="{{ route($tab['route'], ['desde' => $desde, 'hasta' => $hasta]) }}"
               class="rounded-obsidian border px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] transition
                      {{ $tipo === $claveTab ? 'border-primary-container/40 bg-primary-container/10 text-secondary' : 'border-outline-variant/30 bg-surface-container-low text-on-surface-variant hover:text-on-surface' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">{{ $tabs[$tipo]['label'] }}</h3>
        </div>
        <table class="min-w-full text-body-md">
            <thead class="bg-surface-container-low">
                <tr class="text-left">
                    <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">{{ $columna }}</th>
                    <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cantidad</th>
                    <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr class="border-b border-outline-variant/15 last:border-0 hover:bg-primary-container/[0.04]">
                        <td class="px-4 py-3.5 text-on-surface">{{ $fila[$clave] }}</td>
                        <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">{{ $fila['cantidad'] }}</td>
                        <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">Bs {{ number_format($fila['total'], 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-12 text-center font-body-md text-body-md text-outline">No hay datos en el rango seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
