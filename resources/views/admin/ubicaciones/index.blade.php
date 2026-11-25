@extends('layouts.app')

@section('title', 'Ubicaciones')
@section('subtitle', 'Estructura física del almacén')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Almacén</p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">Ubicaciones</h2>
        </div>

        @can('crear ubicaciones')
            <a href="{{ route('admin.ubicaciones.create') }}"
               class="inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-primary-container shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_0_20px_rgba(37,99,235,0.35)] transition hover:bg-primary-container/90">
                Nueva ubicación
            </a>
        @endcan
    </div>

    @foreach (['tienda' => 'Tienda', 'deposito' => 'Depósito'] as $tipo => $etiqueta)
        @php $grupo = $ubicaciones->where('tipo', $tipo); @endphp

        <x-card :title="$etiqueta" :subtitle="$grupo->count().' ubicaciones'">
            <div class="overflow-x-auto">
                <table class="min-w-full text-body-md">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ubicación</th>
                            <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Tipo</th>
                            <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Dirección</th>
                            <th class="px-4 py-3 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</th>
                            <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/15">
                        @forelse ($grupo as $ubicacion)
                            <tr class="transition hover:bg-primary-container/[0.04]">
                                <td class="px-4 py-3.5 font-body-md text-body-md text-on-surface">{{ $ubicacion->nombre_completo }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-obsidian border px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em]
                                        {{ $ubicacion->tipo === 'tienda' ? 'border-secondary/25 bg-secondary/10 text-secondary' : 'border-outline-variant/30 bg-surface-container-low text-on-surface-variant' }}">
                                        {{ $ubicacion->tipo === 'tienda' ? 'Tienda' : 'Depósito' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-on-surface-variant">{{ $ubicacion->direccion ?? '—' }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    @if ($ubicacion->estaActiva())
                                        <span class="inline-flex items-center gap-1.5 rounded-obsidian border border-emerald-500/25 bg-emerald-500/10 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Activa
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">
                                            Inactiva
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-end gap-3">
                                        @can('editar ubicaciones')
                                            <a href="{{ route('admin.ubicaciones.edit', $ubicacion) }}"
                                               class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-secondary transition hover:text-on-surface">Editar</a>
                                        @endcan

                                        @can('eliminar ubicaciones')
                                            <form method="POST" action="{{ route('admin.ubicaciones.destroy', $ubicacion) }}"
                                                  onsubmit="return confirm('¿Está seguro de eliminar esta ubicación?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-error transition hover:text-error/80">
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center font-body-sm text-body-sm text-on-surface-variant">
                                    No hay ubicaciones de este tipo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @endforeach
</div>
@endsection
