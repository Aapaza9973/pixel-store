@extends('layouts.app')

@section('title', 'Ubicaciones')
@section('subtitle', 'Estructura física del almacén')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-white tracking-tight">Ubicaciones</h2>

        @can('crear ubicaciones')
            <a href="{{ route('admin.ubicaciones.create') }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                Nueva ubicación
            </a>
        @endcan
    </div>

    @foreach (['tienda' => 'Tienda', 'deposito' => 'Depósito'] as $tipo => $etiqueta)
        @php $grupo = $ubicaciones->where('tipo', $tipo); @endphp

        <x-card :title="$etiqueta" :subtitle="$grupo->count().' ubicaciones'">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-3 py-3">Ubicación</th>
                            <th class="px-3 py-3">Tipo</th>
                            <th class="px-3 py-3">Dirección</th>
                            <th class="px-3 py-3 text-center">Estado</th>
                            <th class="px-3 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($grupo as $ubicacion)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-3 py-3 text-white font-medium">{{ $ubicacion->nombre_completo }}</td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $ubicacion->tipo === 'tienda' ? 'bg-blue-500/10 text-blue-400' : 'bg-violet-500/10 text-violet-400' }}">
                                        {{ $ubicacion->tipo === 'tienda' ? 'Tienda' : 'Depósito' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-slate-400">{{ $ubicacion->direccion ?? '—' }}</td>
                                <td class="px-3 py-3 text-center">
                                    @if ($ubicacion->estaActiva())
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400">
                                            Activa
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-slate-700/50 text-slate-400">
                                            Inactiva
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        @can('editar ubicaciones')
                                            <a href="{{ route('admin.ubicaciones.edit', $ubicacion) }}"
                                               class="text-xs text-blue-400 hover:text-blue-300 transition">Editar</a>
                                        @endcan

                                        @can('eliminar ubicaciones')
                                            <form method="POST" action="{{ route('admin.ubicaciones.destroy', $ubicacion) }}"
                                                  onsubmit="return confirm('¿Está seguro de eliminar esta ubicación?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-400 hover:text-red-300 transition">
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">
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
