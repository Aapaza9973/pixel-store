@extends('layouts.app')

@section('title', 'Usuarios')
@section('subtitle', 'Gestión del personal con acceso al sistema')

@section('content')
<div class="space-y-6" x-data="{ desactivando: null, motivo: '' }">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="font-body-md text-body-md text-on-surface-variant">
            Total: <span class="tabular-nums text-on-surface">{{ $usuarios->total() }}</span> usuarios
        </p>
        <div class="flex items-center gap-2">
            @can('ver usuarios')
                <a href="{{ route('admin.usuarios.export', request()->query()) }}"
                   class="inline-flex items-center gap-2 rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                    Exportar CSV
                </a>
            @endcan
            @can('crear usuarios')
                <a href="{{ route('admin.usuarios.create') }}"
                   class="inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-primary-container shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_0_20px_rgba(37,99,235,0.35)] transition hover:bg-primary-container/90">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo usuario
                </a>
            @endcan
        </div>
    </div>

    {{-- Filtros --}}
    <x-card>
        <form method="GET" action="{{ route('admin.usuarios.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[200px] flex-1">
                <x-input-label for="buscar" value="Buscar" />
                <x-text-input id="buscar" name="buscar" type="text" :value="request('buscar')"
                              placeholder="Nombre o email…" class="mt-1.5 block w-full" />
            </div>

            <div>
                <x-input-label for="rol" value="Rol" />
                <select name="rol" id="rol"
                        class="mt-1.5 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    <option value="">Todos</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->name }}" @selected(request('rol') === $rol->name)>{{ $rol->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="estado" value="Estado" />
                <select name="estado" id="estado"
                        class="mt-1.5 rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    <option value="">Todos</option>
                    <option value="activos" @selected(request('estado') === 'activos')>Activos</option>
                    <option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option>
                </select>
            </div>

            <div class="flex gap-2">
                <x-primary-button>Filtrar</x-primary-button>
                <a href="{{ route('admin.usuarios.index') }}"
                   class="inline-flex items-center rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                    Limpiar
                </a>
            </div>
        </form>
    </x-card>

    {{-- Tabla --}}
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Usuario</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Roles</th>
                        <th class="px-4 py-3 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</th>
                        <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15">
                    @forelse ($usuarios as $usuario)
                        <tr class="transition hover:bg-primary-container/[0.04]">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-primary-container font-headline-sm text-headline-sm text-on-primary-container">
                                        {{ strtoupper(substr($usuario->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-body-md text-body-md text-on-surface">{{ $usuario->name }}</p>
                                        <p class="truncate font-body-sm text-body-sm text-outline">{{ $usuario->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($usuario->roles as $rol)
                                        @php
                                            $colorRol = match ($rol->name) {
                                                'Admin' => 'border-error/25 bg-error/10 text-error',
                                                'Vendedor' => 'border-secondary/25 bg-secondary/10 text-secondary',
                                                'Cajero' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
                                                'Inventario' => 'border-amber-500/25 bg-amber-500/10 text-amber-400',
                                                default => 'border-outline-variant/30 bg-surface-container-low text-on-surface-variant',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-obsidian border px-2 py-0.5 font-label-sm text-label-sm uppercase tracking-[0.14em] {{ $colorRol }}">
                                            {{ $rol->name }}
                                        </span>
                                    @empty
                                        <span class="font-body-sm text-body-sm text-outline">Sin rol</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($usuario->activo)
                                    <span class="inline-flex items-center gap-1.5 rounded-obsidian border border-emerald-500/25 bg-emerald-500/10 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-emerald-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-obsidian border border-error/25 bg-error/10 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-error">
                                        <span class="h-1.5 w-1.5 rounded-full bg-error"></span>
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="inline-flex items-center gap-3">
                                    <a href="{{ route('admin.usuarios.show', $usuario) }}"
                                       class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                                        Ver
                                    </a>
                                    @can('editar usuarios')
                                        <a href="{{ route('admin.usuarios.edit', $usuario) }}"
                                           class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-secondary transition hover:text-on-surface">
                                            Editar
                                        </a>
                                    @endcan
                                    @can('editar usuarios')
                                        @if ($usuario->activo)
                                            <button type="button"
                                                    @click='desactivando = { id: {{ $usuario->id }}, nombre: {{ json_encode($usuario->name) }}, url: {{ json_encode(route('admin.usuarios.desactivar', $usuario)) }} }; motivo = ""'
                                                    class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-amber-400 transition hover:text-amber-300">
                                                Desactivar
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('admin.usuarios.activar', $usuario) }}"
                                                  onsubmit="return confirm('¿Activar a {{ $usuario->name }}?')" class="inline">
                                                @csrf
                                                <button type="submit" class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-emerald-400 transition hover:text-emerald-300">
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
                            <td colspan="4" class="px-4 py-12 text-center font-body-sm text-body-sm text-on-surface-variant">
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

    {{-- Modal de desactivación con motivo opcional (HU-1.8) --}}
    <div x-show="desactivando" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
         @click.self="desactivando = null">
        <div class="w-full max-w-md rounded-obsidian-xl border border-outline-variant/30 bg-surface-container p-6 shadow-2xl">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Desactivar usuario</h3>
            <p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">
                Vas a desactivar la cuenta de
                <span class="font-body-md text-body-md text-on-surface" x-text="desactivando?.nombre"></span>.
            </p>

            <form method="POST" :action="desactivando?.url" class="mt-5 space-y-4">
                @csrf
                <div>
                    <x-input-label for="motivo" value="Motivo (opcional)" />
                    <textarea name="motivo" id="motivo" x-model="motivo" rows="3"
                              placeholder="Ej.: finalización de contrato, vacaciones, etc."
                              class="mt-1.5 w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25"></textarea>
                </div>

                <div class="flex items-center gap-3">
                    <x-danger-button>Desactivar</x-danger-button>
                    <x-secondary-button type="button" x-on:click="desactivando = null">Cancelar</x-secondary-button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
