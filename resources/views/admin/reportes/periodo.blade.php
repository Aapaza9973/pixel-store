@extends('layouts.app')

@section('title', 'Reportes por período')
@section('subtitle', 'Totales cobrados por día, semana, mes o año')

@section('content')
@php
    $etiquetas = ['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes', 'anio' => 'Año'];

    // El gráfico muestra los últimos 24 períodos del rango: la tabla tiene todo.
    $grafico = array_slice($filas, -24);
    $maximo = collect($grafico)->max('total_ventas') ?: 0;
    $ancho = 720;
    $alto = 180;
    $separacion = 6;
    $cantidad = max(count($grafico), 1);
    $anchoBarra = ($ancho - ($separacion * ($cantidad - 1))) / $cantidad;
@endphp

<div class="space-y-6">

    {{-- Filtros --}}
    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <form method="GET" action="{{ route('admin.reportes.periodo') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="desde" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Desde</label>
                <input type="date" name="desde" id="desde" value="{{ $desde }}"
                       class="rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-primary-container">
                @error('desde') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="hasta" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Hasta</label>
                <input type="date" name="hasta" id="hasta" value="{{ $hasta }}"
                       class="rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-primary-container">
                @error('hasta') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="agrupacion" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Agrupación</label>
                <select name="agrupacion" id="agrupacion"
                        class="rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-primary-container">
                    @foreach ($etiquetas as $valor => $etiqueta)
                        <option value="{{ $valor }}" {{ $agrupacion === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('agrupacion') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">
                Generar
            </button>
        </form>
    </div>

    {{-- Totales del rango --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total cobrado</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-on-surface">
                Bs {{ number_format($totalVentas, 2, '.', ',') }}
            </p>
        </div>
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ventas cobradas</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-secondary">{{ $cantidadVentas }}</p>
        </div>
        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Períodos con ventas</p>
            <p class="mt-2 font-headline-md text-headline-md tabular-nums text-on-surface">{{ count($filas) }}</p>
        </div>
    </div>

    {{-- Gráfico: SVG inline, sin librerías externas --}}
    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="font-headline-sm text-headline-sm text-on-surface">Evolución del período</h3>
                <p class="mt-0.5 font-body-sm text-body-sm text-on-surface-variant">
                    {{ $etiquetas[$agrupacion] }} · últimos {{ count($grafico) }} con ventas
                </p>
            </div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                máximo Bs {{ number_format((float) $maximo, 2, '.', ',') }}
            </p>
        </div>

        @if (empty($grafico))
            <p class="py-12 text-center font-body-md text-body-md text-outline">
                No hay ventas cobradas en el rango seleccionado.
            </p>
        @else
            <svg viewBox="0 0 {{ $ancho }} {{ $alto + 10 }}" class="h-56 w-full" role="img"
                 aria-label="Totales cobrados por {{ strtolower($etiquetas[$agrupacion]) }}">
                <line x1="0" y1="{{ $alto }}" x2="{{ $ancho }}" y2="{{ $alto }}"
                      stroke="#2f3445" stroke-width="1" />
                @foreach ($grafico as $indice => $fila)
                    @php
                        $altura = $maximo > 0 ? ($fila['total_ventas'] / $maximo) * ($alto - 8) : 0;
                        $x = $indice * ($anchoBarra + $separacion);
                    @endphp
                    <rect x="{{ round($x, 2) }}"
                          y="{{ round($alto - $altura, 2) }}"
                          width="{{ round($anchoBarra, 2) }}"
                          height="{{ round($altura, 2) }}"
                          rx="2" fill="#2563eb" fill-opacity="0.7">
                        <title>{{ $fila['periodo'] }} · Bs {{ number_format($fila['total_ventas'], 2, '.', ',') }}</title>
                    </rect>
                @endforeach
            </svg>
            <div class="mt-2 flex justify-between font-label-sm text-label-sm text-outline">
                <span>{{ $grafico[0]['periodo'] }}</span>
                <span>{{ $grafico[count($grafico) - 1]['periodo'] }}</span>
            </div>
        @endif
    </div>

    {{-- Tabla --}}
    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="border-b border-outline-variant/30 bg-surface-container-low px-5 py-4">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Detalle por período</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Período</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cantidad</th>
                        <th class="px-4 py-3.5 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $fila)
                        <tr class="border-b border-outline-variant/15 last:border-0 hover:bg-primary-container/[0.04]">
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface">{{ $fila['periodo'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface-variant">{{ $fila['cantidad_ventas'] }}</td>
                            <td class="px-4 py-3.5 text-right tabular-nums text-on-surface">
                                Bs {{ number_format($fila['total_ventas'], 2, '.', ',') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-12 text-center font-body-md text-body-md text-outline">
                                No hay ventas cobradas en el rango seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if (! empty($filas))
                    <tfoot>
                        <tr class="bg-surface-container-low">
                            <td class="px-4 py-3.5 font-label-md text-label-md uppercase tracking-[0.14em] text-outline">Totales</td>
                            <td class="px-4 py-3.5 text-right font-label-md text-label-md tabular-nums text-on-surface">{{ $cantidadVentas }}</td>
                            <td class="px-4 py-3.5 text-right font-label-md text-label-md tabular-nums text-on-surface">
                                Bs {{ number_format($totalVentas, 2, '.', ',') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
