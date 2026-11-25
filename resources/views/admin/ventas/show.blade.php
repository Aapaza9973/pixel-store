@extends('layouts.app')

@section('title', 'Venta #'.str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT))
@section('subtitle', 'Detalle de venta y devoluciones')

@php
    $numero = '#'.str_pad((string) $venta->id, 6, '0', STR_PAD_LEFT);
    $estados = [
        'Pagado' => ['Pagado', 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30'],
        'parcialmente_devuelta' => ['Parcialmente devuelta', 'bg-amber-500/10 text-amber-300 border-amber-500/30'],
        'totalmente_devuelta' => ['Totalmente devuelta', 'bg-red-500/10 text-red-300 border-red-500/30'],
        'Cancelada' => ['Cancelada', 'bg-surface-container/10 text-on-surface-variant border-outline-variant/30'],
        'Pendiente' => ['Pendiente', 'bg-surface-container/10 text-on-surface-variant border-outline-variant/30'],
    ];
    [$estadoTexto, $estadoClases] = $estados[$venta->estado] ?? [$venta->estado, 'bg-surface-container/10 text-on-surface-variant border-outline-variant/30'];
    $lineasDevolubles = collect($venta->detalles)->filter(fn ($d) => ($pendientes[$d->id] ?? 0) > 0)->values();
@endphp

@section('content')
<div class="space-y-6">

    @if (session('success'))
        <x-alert type="success" message="{{ session('success') }}" />
    @endif

    @if (session('error'))
        <x-alert type="error" message="{{ session('error') }}" />
    @endif

    {{-- Cabecera --}}
    <x-card>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm text-on-surface-variant">Venta</p>
                <p class="text-2xl font-semibold tabular-nums text-on-surface">{{ $numero }}</p>
                <p class="mt-1 text-sm text-on-surface-variant">
                    {{ $venta->fecha?->format('d/m/Y H:i') }}
                    · Cliente: {{ $venta->cliente?->nombre ?? 'Consumidor final' }}
                    · Vendedor: {{ $venta->vendedor?->name ?? '—' }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span class="rounded-full border px-3 py-1 text-xs font-medium {{ $estadoClases }}">
                    {{ $estadoTexto }}
                </span>

                <a href="{{ route('admin.ventas.comprobante', ['venta' => $venta, 'formato' => 'ticket']) }}"
                   class="text-sm text-secondary hover:text-on-surface">Ticket</a>

                <a href="{{ route('admin.ventas.comprobante', ['venta' => $venta, 'formato' => 'a4']) }}"
                   class="text-sm text-secondary hover:text-on-surface">A4</a>

                @can('crear devoluciones')
                    @if ($puedeDevolver)
                        <button type="button" x-data="{}" x-on:click="$dispatch('abrir-devolucion')"
                                class="rounded-obsidian-lg bg-primary-container px-4 py-2 text-sm font-medium text-on-surface bg-primary-container">
                            Devolver
                        </button>
                    @endif
                @endcan

                @if ($venta->estado === 'Pagado' && auth()->user()?->hasRole('Admin'))
                    <form method="POST" action="{{ route('admin.ventas.anular', $venta) }}"
                          onsubmit="return confirm('¿Anular la venta {{ $numero }}? Se reingresará el stock y la venta quedará cancelada.');">
                        @csrf
                        <button type="submit"
                                class="rounded-obsidian-lg border border-red-500/40 px-4 py-2 text-sm font-medium text-red-300 transition hover:bg-red-500/10">
                            Anular Venta
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-card>

    {{-- Líneas --}}
    <x-card title="Productos">
        <table class="w-full text-sm">
            <thead class="text-left text-on-surface-variant">
                <tr>
                    <th class="py-2">Producto</th>
                    <th class="py-2 text-right">Cant.</th>
                    <th class="py-2 text-right">Precio</th>
                    <th class="py-2 text-right">Subtotal</th>
                    <th class="py-2 text-right">Pendiente</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/15 text-on-surface">
                @foreach ($venta->detalles as $detalle)
                    <tr>
                        <td class="py-2">
                            {{ $detalle->producto?->nombre ?? 'Producto #'.$detalle->producto_id }}
                            @if ($detalle->numeroSerie)
                                <span class="block text-xs text-on-surface-variant">SN {{ $detalle->numeroSerie->numero_serie }}</span>
                            @endif
                        </td>
                        <td class="py-2 text-right tabular-nums">{{ $detalle->cantidad }}</td>
                        <td class="py-2 text-right tabular-nums">{{ \App\Support\Dinero::formatear((float) $detalle->precio_unitario) }}</td>
                        <td class="py-2 text-right tabular-nums">{{ \App\Support\Dinero::formatear($detalle->subtotal()) }}</td>
                        <td class="py-2 text-right tabular-nums">{{ $pendientes[$detalle->id] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>

    {{-- Pagos --}}
    <x-card title="Pagos">
        <ul class="space-y-1 text-sm text-on-surface">
            @foreach ($venta->pagos as $pago)
                <li class="flex justify-between">
                    <span class="text-on-surface-variant">{{ $pago->metodo }}</span>
                    <span class="tabular-nums">{{ \App\Support\Dinero::formatear((float) $pago->monto) }}</span>
                </li>
            @endforeach
        </ul>
        <p class="mt-3 flex justify-between border-t border-outline-variant/15 pt-2 text-on-surface">
            <span>Total</span>
            <span class="tabular-nums">{{ \App\Support\Dinero::formatear((float) $venta->total) }}</span>
        </p>
    </x-card>

    {{-- Historial de devoluciones --}}
    <x-card title="Devoluciones">
        @forelse ($venta->devoluciones as $devolucion)
            <div class="border-b border-outline-variant/15 py-3 last:border-0">
                <p class="text-sm text-on-surface">
                    Devolución #{{ $devolucion->id }}
                    · {{ $devolucion->created_at?->format('d/m/Y H:i') }}
                    · {{ \App\Support\Dinero::formatear((float) $devolucion->total_devuelto) }}
                    · {{ $devolucion->estado }}
                </p>
                <p class="text-xs text-on-surface-variant">Motivo: {{ $devolucion->motivo }} · {{ $devolucion->user?->name }}</p>
                <ul class="mt-1 text-xs text-on-surface-variant">
                    @foreach ($devolucion->detalles as $linea)
                        <li>{{ $linea->cantidad }} × {{ $linea->ventaDetalle?->producto?->nombre ?? 'Producto' }}</li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="text-sm text-on-surface-variant">Sin devoluciones registradas.</p>
        @endforelse
    </x-card>

    {{-- Modal de devolución --}}
    @can('crear devoluciones')
        @if ($puedeDevolver)
            <div x-data="devolucionForm()" x-on:abrir-devolucion.window="abrir()" x-show="abierto" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                <div class="w-full max-w-xl rounded-obsidian-xl border border-outline-variant/15 bg-surface-container-lowest p-6" x-on:click.outside="cerrar()">
                    <h2 class="text-lg font-semibold text-on-surface">Registrar devolución</h2>

                    <div class="mt-4 space-y-3">
                        @foreach ($lineasDevolubles as $linea)
                            <div class="flex items-center gap-3">
                                <input type="checkbox" class="rounded border-outline-variant/30 bg-surface"
                                       x-model="lineas[{{ $linea->id }}].marcada">
                                <span class="flex-1 text-sm text-on-surface">
                                    {{ $linea->producto?->nombre ?? 'Producto #'.$linea->producto_id }}
                                    <span class="block text-xs text-on-surface-variant">Disponible: {{ $pendientes[$linea->id] }}</span>
                                </span>
                                <input type="number" min="1" max="{{ $pendientes[$linea->id] }}"
                                       class="w-24 rounded-obsidian-lg border-outline-variant/30 bg-surface text-on-surface"
                                       x-model.number="lineas[{{ $linea->id }}].cantidad"
                                       x-bind:disabled="!lineas[{{ $linea->id }}].marcada">
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <label class="text-sm text-on-surface-variant" for="motivo-devolucion">Motivo</label>
                        <textarea id="motivo-devolucion" rows="2" maxlength="500" x-model="motivo"
                                  class="mt-1 w-full rounded-obsidian-lg border-outline-variant/30 bg-surface text-on-surface"></textarea>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <input type="checkbox" id="requiere-aprobacion" class="rounded border-outline-variant/30 bg-surface"
                               x-model="requiereAprobacion">
                        <label for="requiere-aprobacion" class="text-sm text-on-surface-variant">
                            Requiere aprobación (queda pendiente, sin mover stock)
                        </label>
                    </div>

                    <template x-if="error">
                        <p class="mt-4 rounded-obsidian-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-300" x-text="error"></p>
                    </template>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" x-on:click="cerrar()" class="text-sm text-on-surface-variant hover:text-on-surface">Cancelar</button>
                        <button type="button" x-on:click="enviar()" x-bind:disabled="enviando"
                                class="rounded-obsidian-lg bg-primary-container px-4 py-2 text-sm font-medium text-on-surface bg-primary-container disabled:opacity-50">
                            Registrar devolución
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endcan
</div>

@if ($puedeDevolver)
    <script>
        function devolucionForm() {
            const pendientes = @json($pendientes);
            const lineas = {};

            Object.keys(pendientes).forEach((id) => {
                lineas[id] = { marcada: false, cantidad: 1 };
            });

            return {
                abierto: false,
                enviando: false,
                error: '',
                motivo: '',
                requiereAprobacion: false,
                lineas,

                abrir() {
                    this.error = '';
                    this.abierto = true;
                },

                cerrar() {
                    this.abierto = false;
                },

                async enviar() {
                    this.error = '';

                    const items = Object.entries(this.lineas)
                        .filter(([, linea]) => linea.marcada)
                        .map(([id, linea]) => ({
                            venta_detalle_id: Number(id),
                            cantidad: Number(linea.cantidad),
                        }));

                    if (items.length === 0) {
                        this.error = 'Seleccioná al menos un producto a devolver.';
                        return;
                    }

                    this.enviando = true;

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        const url = this.requiereAprobacion
                            ? '{{ route('admin.devoluciones.solicitar', $venta) }}'
                            : '{{ route('admin.ventas.devoluciones.store', $venta) }}';

                        const respuesta = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({
                                operacion_id: crypto.randomUUID(),
                                motivo: this.motivo,
                                items,
                            }),
                        });

                        if (respuesta.status === 201) {
                            window.location.reload();
                            return;
                        }

                        const datos = await respuesta.json().catch(() => ({}));
                        const errores = datos.errors ? Object.values(datos.errors).flat() : [];
                        this.error = errores[0] || datos.message || 'No se pudo registrar la devolución.';
                    } catch (e) {
                        this.error = 'Sin conexión. Intentá de nuevo.';
                    } finally {
                        this.enviando = false;
                    }
                },
            };
        }
    </script>
@endif
@endsection
