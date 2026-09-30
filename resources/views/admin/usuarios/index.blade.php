@extends('layouts.app')

@section('title', 'Usuarios')
@section('subtitle', 'Gestión del personal con acceso al sistema')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-400">
            Total: <span class="text-white font-semibold">{{ $usuarios->total() }}</span> usuarios
        </p>
        @can('crear usuarios')
            <a href="{{ route('admin.usuarios.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo usuario
            </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <x-card>
        <form method="GET" action="{{ route('admin.usuarios.index') }}"
              class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label for="buscar" class="block text-xs font-semibold text-slate-400 uppercase mb-1">
                    Buscar
                </label>
                <input type="text" name="buscar" id="buscar" value="{{ request('buscar') }}"
                       placeholder="Nombre o email..."
                       class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white text-sm focus:border-blue-500 focus:outline-none">
            </div>

            <div>
                <label for="rol" class="block text-xs font-semibold text-slate-400 uppercase mb-1">
                    Rol
                </label>
                <select name="rol" id="rol"
                        class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white text-sm focus:border-blue-500 focus:outline-none">
                    <option value="">Todos</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->name }}" {{ request('rol') === $rol->name ? 'selected' : '' }}>
                            {{ $rol->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="estado" class="block text-xs font-semibold text-slate-400 uppercase mb-1">
                    Estado
                </label>
                <select name="estado" id="estado"
                        class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white text-sm focus:border-blue-500 focus:outline-none">
                    <option value="">Todos</option>
                    <option value="activos" {{ request('estado') === 'activos' ? 'selected' : '' }}>Activos</option>
                    <option value="inactivos" {{ request('estado') === 'inactivos' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    Filtrar
                </button>
                <a href="{{ route('admin.usuarios.index') }}"
                   class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                    Limpiar
                </a>
            </div>
        </form>
    </x-card>

    {{-- Tabla --}}
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-3 py-3">Usuario</th>
                        <th class="px-3 py-3">Roles</th>
                        <th class="px-3 py-3 text-center">Estado</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($usuarios as $usuario)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                                        {{ strtoupper(substr($usuario->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-white font-medium truncate">{{ $usuario->name }}</p>
                                        <p class="text-xs text-slate-500 truncate">{{ $usuario->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($usuario->roles as $rol)
                                        @php
                                            $colorRol = match ($rol->name) {
                                                'Admin' => 'bg-red-500/10 text-red-400',
                                                'Vendedor' => 'bg-blue-500/10 text-blue-400',
                                                'Cajero' => 'bg-emerald-500/10 text-emerald-400',
                                                'Inventario' => 'bg-purple-500/10 text-purple-400',
                                                default => 'bg-slate-500/10 text-slate-400',
                                            };
                                        @endphp
                                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-md {{ $colorRol }}">
                                            {{ $rol->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-500">Sin rol</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($usuario->activo)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-medium rounded-md bg-emerald-500/10 text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-medium rounded-md bg-red-500/10 text-red-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.usuarios.show', $usuario) }}"
                                       class="text-xs text-slate-400 hover:text-white font-medium">
                                        Ver
                                    </a>
                                    @can('editar usuarios')
                                        <a href="{{ route('admin.usuarios.edit', $usuario) }}"
                                           class="text-xs text-blue-400 hover:text-blue-300 font-medium">
                                            Editar
                                        </a>
                                    @endcan
                                    @can('editar usuarios')
                                        @if ($usuario->activo)
                                            <form method="POST" action="{{ route('admin.usuarios.desactivar', $usuario) }}"
                                                  onsubmit="return confirm('¿Desactivar a {{ $usuario->name }}?')" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs text-amber-400 hover:text-amber-300 font-medium">
                                                    Desactivar
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.usuarios.activar', $usuario) }}"
                                                  onsubmit="return confirm('¿Activar a {{ $usuario->name }}?')" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs text-emerald-400 hover:text-emerald-300 font-medium">
                                                    Activar
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-12 text-center text-sm text-slate-500">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($usuarios->hasPages())
            <div class="mt-4">{{ $usuarios->links() }}</div>
        @endif
    </x-card>
</div>
@endsection

