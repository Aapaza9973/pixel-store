@extends('layouts.app')

@section('title', $usuario->name)
@section('subtitle', 'Detalle del usuario')

@section('content')
<div class="max-w-4xl space-y-6">

    {{-- Encabezado con acciones --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-lg">
                {{ strtoupper(substr($usuario->name, 0, 1)) }}
            </div>
            <div>
                <p class="text-xs text-slate-500">ID: {{ $usuario->id }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @can('view', $usuario)
                <a href="{{ route('admin.usuarios.historial', $usuario) }}"
                   class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                    Ver historial completo
                </a>
            @endcan
            @can('editar usuarios')
                <a href="{{ route('admin.usuarios.edit', $usuario) }}"
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    Editar
                </a>
            @endcan
            <a href="{{ route('admin.usuarios.index') }}"
               class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                Volver
            </a>
        </div>
    </div>

    {{-- Información personal --}}
    <x-card title="Información personal">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Nombre</dt>
                <dd class="text-white">{{ $usuario->name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Email</dt>
                <dd class="text-white">{{ $usuario->email }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Teléfono</dt>
                <dd class="text-slate-300">{{ $usuario->telefono ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">NIT / CI</dt>
                <dd class="text-slate-300">{{ $usuario->nit_ci ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Estado</dt>
                <dd class="{{ $usuario->activo ? 'text-emerald-400' : 'text-red-400' }}">
                    {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                </dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Registrado</dt>
                <dd class="text-slate-300">{{ $usuario->created_at->format('d/m/Y H:i') }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Roles asignados</dt>
                <dd class="flex flex-wrap gap-1 mt-1">
                    @forelse ($usuario->roles as $rol)
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-md bg-blue-500/10 text-blue-400">
                            {{ $rol->name }}
                        </span>
                    @empty
                        <span class="text-xs text-slate-500">Sin rol</span>
                    @endforelse
                </dd>
            </div>
        </dl>
    </x-card>

    {{-- Últimos accesos --}}
    <x-card title="Últimos accesos" subtitle="Últimos 20 eventos de auditoría">
        @if ($ultimosAccesos->isEmpty())
            <p class="text-sm text-slate-500 text-center py-6">
                Sin registros de accesos todavía.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-3 py-2">Acción</th>
                            <th class="px-3 py-2">IP</th>
                            <th class="px-3 py-2">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach ($ultimosAccesos as $acceso)
                            <tr>
                                <td class="px-3 py-2">
                                    @php
                                        $badge = match ($acceso->accion) {
                                            'login' => 'bg-emerald-500/10 text-emerald-400',
                                            'logout' => 'bg-slate-500/10 text-slate-400',
                                            'login_fallido' => 'bg-red-500/10 text-red-400',
                                            default => 'bg-slate-500/10 text-slate-400',
                                        };
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-md {{ $badge }}">
                                        {{ $acceso->accion }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-slate-400 font-mono text-xs">{{ $acceso->ip ?? '—' }}</td>
                                <td class="px-3 py-2 text-slate-400 text-xs">
                                    {{ $acceso->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

</div>
@endsection

