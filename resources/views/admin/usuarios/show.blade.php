@extends('layouts.app')

@section('title', $usuario->name)
@section('subtitle', 'Detalle del usuario')

@section('content')
<div class="max-w-4xl space-y-6">

    {{-- Encabezado con acciones --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-container font-headline-md text-headline-md text-on-primary-container">
                {{ strtoupper(substr($usuario->name, 0, 1)) }}
            </div>
            <div>
                <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">ID {{ $usuario->id }}</p>
                <p class="font-headline-sm text-headline-sm text-on-surface">{{ $usuario->name }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('view', $usuario)
                <a href="{{ route('admin.usuarios.historial', $usuario) }}"
                   class="inline-flex items-center rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                    Ver historial completo
                </a>
            @endcan
            @can('editar usuarios')
                <a href="{{ route('admin.usuarios.edit', $usuario) }}"
                   class="inline-flex items-center rounded-obsidian-lg bg-primary-container px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-primary-container shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_0_20px_rgba(37,99,235,0.35)] transition hover:bg-primary-container/90">
                    Editar
                </a>
            @endcan
            <a href="{{ route('admin.usuarios.index') }}"
               class="inline-flex items-center rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
                Volver
            </a>
        </div>
    </div>

    {{-- Información personal --}}
    <x-card title="Información personal">
        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Nombre</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $usuario->name }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Email</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $usuario->email }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Teléfono</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface-variant">{{ $usuario->telefono ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">NIT / CI</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface-variant">{{ $usuario->nit_ci ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</dt>
                <dd class="mt-1">
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
                </dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Registrado</dt>
                <dd class="mt-1 font-body-md text-body-md tabular-nums text-on-surface-variant">{{ $usuario->created_at->format('d/m/Y H:i') }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Roles asignados</dt>
                <dd class="mt-1.5 flex flex-wrap gap-1">
                    @forelse ($usuario->roles as $rol)
                        <span class="inline-flex rounded-obsidian border border-secondary/25 bg-secondary/10 px-2 py-0.5 font-label-sm text-label-sm uppercase tracking-[0.14em] text-secondary">
                            {{ $rol->name }}
                        </span>
                    @empty
                        <span class="font-body-sm text-body-sm text-outline">Sin rol</span>
                    @endforelse
                </dd>
            </div>
        </dl>
    </x-card>

    {{-- Últimos accesos --}}
    <x-card title="Últimos accesos" subtitle="Últimos 20 eventos de auditoría">
        @if ($ultimosAccesos->isEmpty())
            <p class="py-6 text-center font-body-sm text-body-sm text-outline">Sin registros de accesos todavía.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-body-md">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acción</th>
                            <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">IP</th>
                            <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/15">
                        @foreach ($ultimosAccesos as $acceso)
                            <tr class="transition hover:bg-primary-container/[0.04]">
                                <td class="px-4 py-3">
                                    @php
                                        $badge = match ($acceso->accion) {
                                            'login' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
                                            'login_fallido' => 'border-error/25 bg-error/10 text-error',
                                            default => 'border-outline-variant/30 bg-surface-container-low text-on-surface-variant',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-obsidian border px-2 py-0.5 font-label-sm text-label-sm uppercase tracking-[0.14em] {{ $badge }}">
                                        {{ $acceso->accion }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-body-sm text-on-surface-variant">{{ $acceso->ip ?? '—' }}</td>
                                <td class="px-4 py-3 font-body-sm text-body-sm tabular-nums text-on-surface-variant">
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
