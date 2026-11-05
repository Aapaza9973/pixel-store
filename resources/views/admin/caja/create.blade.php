@extends('layouts.app')

@section('title', 'Nuevo cierre de caja')
@section('subtitle', 'Conteo de efectivo por turno')

@section('content')
@php
    $metodos = \App\Services\CierreCajaService::METODOS;
@endphp

<div class="mx-auto max-w-3xl space-y-6"
     x-data="{
        efectivoReal: {{ (float) old('montos.efectivo', 0) }},
        efectivoEsperado: {{ (float) $resumen['efectivo_esperado'] }},
        get diferencia() {
            return Math.round((Number(this.efectivoReal || 0) - this.efectivoEsperado) * 100) / 100;
        },
     }">

    {{-- Resumen del sistema para la selección actual --}}
    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cierre de</p>
                <p class="mt-1 font-headline-sm text-headline-sm text-on-surface">
                    {{ $vendedorSeleccionado ?? auth()->user()->name }}
                    · {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}
                    · {{ ucfirst($turno) }}
                </p>
            </div>
            <a href="{{ route('admin.caja.reporte', ['fecha' => $fecha]) }}"
               class="font-body-sm text-body-sm text-secondary transition hover:text-on-surface">
                Ver reporte del día
            </a>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-obsidian border border-outline-variant/25 bg-surface-container-low px-3 py-2">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">Total cobrado</p>
                <p class="mt-1 font-body-md text-body-md tabular-nums text-on-surface">
                    Bs {{ number_format($resumen['total_ventas'], 2, '.', ',') }}
                </p>
            </div>
            <div class="rounded-obsidian border border-outline-variant/25 bg-surface-container-low px-3 py-2">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">Devoluciones</p>
                <p class="mt-1 font-body-md text-body-md tabular-nums text-on-surface">
                    Bs {{ number_format($resumen['total_devoluciones'], 2, '.', ',') }}
                </p>
            </div>
            <div class="rounded-obsidian border border-outline-variant/25 bg-surface-container-low px-3 py-2">
                <p class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">Efectivo esperado</p>
                <p class="mt-1 font-body-md text-body-md tabular-nums text-secondary">
                    Bs {{ number_format($resumen['efectivo_esperado'], 2, '.', ',') }}
                </p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.caja.store') }}" class="space-y-6">
        @csrf

        {{-- Selección --}}
        <div class="space-y-5 rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <h3 class="font-label-md text-label-md uppercase tracking-[0.18em] text-outline">Turno a cerrar</h3>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="fecha" class="mb-1.5 block font-body-sm text-body-sm text-on-surface-variant">
                        Fecha <span class="text-error">*</span>
                    </label>
                    <input type="date" name="fecha" id="fecha" value="{{ old('fecha', $fecha) }}"
                           class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-primary-container">
                    @error('fecha') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="turno" class="mb-1.5 block font-body-sm text-body-sm text-on-surface-variant">
                        Turno <span class="text-error">*</span>
                    </label>
                    <select name="turno" id="turno"
                            class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-primary-container">
                        @foreach (\App\Services\CierreCajaService::TURNOS as $opcion)
                            @php $cerrado = in_array($opcion, $turnosCerrados, true); @endphp
                            <option value="{{ $opcion }}"
                                    {{ old('turno', $turno) === $opcion ? 'selected' : '' }}
                                    {{ $cerrado ? 'disabled' : '' }}>
                                {{ ucfirst($opcion) }}{{ $cerrado ? ' (ya cerrado)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('turno') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    @if ($esAdmin)
                        <label for="user_id" class="mb-1.5 block font-body-sm text-body-sm text-on-surface-variant">
                            Vendedor
                        </label>
                        <select name="user_id" id="user_id"
                                class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-primary-container">
                            <option value="{{ auth()->id() }}">Cerrar mi turno ({{ auth()->user()->name }})</option>
                            @foreach ($vendedores as $vendedor)
                                @continue($vendedor->id === auth()->id())
                                <option value="{{ $vendedor->id }}" {{ (int) old('user_id', $userId) === $vendedor->id ? 'selected' : '' }}>
                                    {{ $vendedor->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                    @else
                        <label class="mb-1.5 block font-body-sm text-body-sm text-on-surface-variant">Vendedor</label>
                        <p class="rounded-obsidian border border-outline-variant/25 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                            {{ auth()->user()->name }} (mi turno)
                        </p>
                    @endif
                </div>
            </div>

            <button type="submit" formmethod="GET" formaction="{{ route('admin.caja.create') }}"
                    class="rounded-obsidian border border-outline-variant/40 px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface transition hover:border-secondary hover:text-secondary">
                Recalcular montos
            </button>
        </div>

        {{-- Conteo real --}}
        <div class="space-y-5 rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <div>
                <h3 class="font-label-md text-label-md uppercase tracking-[0.18em] text-outline">Montos contados en caja</h3>
                <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                    Solo se muestran los métodos con movimientos en el turno.
                </p>
            </div>

            @foreach ($montos as $metodo => $montoSistema)
                @php $clave = strtolower($metodo); @endphp
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-outline-variant/15 pb-4 last:border-0 last:pb-0">
                    <div>
                        <p class="font-body-md text-body-md text-on-surface">{{ $metodo }}</p>
                        <p class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">
                            Sistema: Bs {{ number_format((float) $montoSistema, 2, '.', ',') }}
                        </p>
                    </div>

                    <div class="w-full sm:w-56">
                        <label for="monto-{{ $clave }}" class="mb-1 block font-body-sm text-body-sm text-on-surface-variant">
                            Real
                        </label>
                        <input type="number" step="0.01" min="0"
                               name="montos[{{ $clave }}]" id="monto-{{ $clave }}"
                               value="{{ old('montos.'.$clave, number_format((float) $montoSistema, 2, '.', '')) }}"
                               @if ($clave === 'efectivo') x-model.number="efectivoReal" @endif
                               class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md tabular-nums text-on-surface focus:border-primary-container focus:ring-primary-container">
                        @error('montos.'.$clave) <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach

            {{-- Diferencia de efectivo en vivo --}}
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-obsidian border border-outline-variant/25 bg-surface-container-low px-4 py-3"
                 x-bind:class="Math.abs(diferencia) < 0.005 ? 'border-emerald-500/30' : 'border-error/40'">
                <div>
                    <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Diferencia de efectivo</p>
                    <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                        real − esperado (Bs {{ number_format($resumen['efectivo_esperado'], 2, '.', ',') }})
                    </p>
                </div>
                <p class="font-headline-sm text-headline-sm tabular-nums"
                   x-bind:class="Math.abs(diferencia) < 0.005 ? 'text-emerald-400' : 'text-error'"
                   x-text="'Bs ' + diferencia.toFixed(2)">Bs 0.00</p>
            </div>

            <div>
                <label for="notas" class="mb-1.5 block font-body-sm text-body-sm text-on-surface-variant">Notas</label>
                <textarea name="notas" id="notas" rows="2" maxlength="1000"
                          class="w-full rounded-obsidian border-outline-variant/40 bg-surface-container-low font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-primary-container">{{ old('notas') }}</textarea>
                @error('notas') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">
                Registrar cierre
            </button>
            <a href="{{ route('admin.caja.index', ['fecha' => $fecha]) }}"
               class="rounded-obsidian border border-outline-variant/40 px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
