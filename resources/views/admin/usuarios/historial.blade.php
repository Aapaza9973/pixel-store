@extends('layouts.app')

@section('title', 'Historial de accesos')
@section('subtitle', 'Auditoría del usuario '.$usuario->name)

@section('content')
<div class="space-y-6">

    {{-- Ficha del usuario auditado --}}
    <x-card>
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-primary-container font-headline-md text-headline-md text-on-primary-container">
                {{ strtoupper(substr($usuario->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="font-body-md text-body-md text-on-surface">{{ $usuario->name }}</p>
                <p class="font-body-sm text-body-sm text-outline">{{ $usuario->email }}</p>
                <div class="mt-1.5 flex flex-wrap gap-1">
                    @forelse ($usuario->roles as $rol)
                        <span class="inline-flex rounded-obsidian border border-secondary/25 bg-secondary/10 px-2 py-0.5 font-label-sm text-label-sm uppercase tracking-[0.14em] text-secondary">
                            {{ $rol->name }}
                        </span>
                    @empty
                        <span class="font-body-sm text-body-sm text-outline">Sin rol</span>
                    @endforelse
                </div>
            </div>
            <div class="ml-auto font-body-sm text-body-sm text-on-surface-variant">
                Total: <span class="tabular-nums text-on-surface">{{ $logs->total() }}</span> eventos
            </div>
        </div>
    </x-card>

    {{-- Registros de auditoría --}}
    <x-card title="Registro de auditoría" subtitle="Últimos 20 eventos por página">
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Fecha</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acción</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">IP</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Navegador</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Datos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15">
                    @forelse ($logs as $log)
                        <tr class="transition hover:bg-primary-container/[0.04]">
                            <td class="whitespace-nowrap px-4 py-3 font-body-sm text-body-sm tabular-nums text-on-surface-variant">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badge = match ($log->accion) {
                                        'login' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400',
                                        'login_fallido' => 'border-error/25 bg-error/10 text-error',
                                        'crear_user' => 'border-secondary/25 bg-secondary/10 text-secondary',
                                        'editar_user' => 'border-amber-500/25 bg-amber-500/10 text-amber-400',
                                        'eliminar_user' => 'border-error/25 bg-error/10 text-error',
                                        'cambiar_roles_user' => 'border-tertiary/25 bg-tertiary/10 text-tertiary',
                                        'acceso_denegado_sin_rol' => 'border-error/25 bg-error/10 text-error',
                                        default => 'border-outline-variant/30 bg-surface-container-low text-on-surface-variant',
                                    };
                                @endphp
                                <span class="inline-flex rounded-obsidian border px-2 py-0.5 font-label-sm text-label-sm uppercase tracking-[0.14em] {{ $badge }}">
                                    {{ $log->accion }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-body-sm text-on-surface-variant">{{ $log->ip ?? '—' }}</td>
                            <td class="max-w-[220px] truncate px-4 py-3 font-body-sm text-body-sm text-outline" title="{{ $log->user_agent }}">
                                {{ $log->user_agent ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($log->datos_anteriores !== null || $log->datos_nuevos !== null)
                                    <details>
                                        <summary class="cursor-pointer select-none font-label-sm text-label-sm uppercase tracking-[0.14em] text-secondary transition hover:text-on-surface">
                                            Ver datos
                                        </summary>
                                        <div class="mt-2 max-w-md space-y-2">
                                            @if ($log->datos_anteriores !== null)
                                                <div>
                                                    <p class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">Antes</p>
                                                    <pre class="mt-0.5 whitespace-pre-wrap break-all rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-2 py-1 font-mono text-body-sm text-on-surface-variant">{{ json_encode($log->datos_anteriores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            @endif
                                            @if ($log->datos_nuevos !== null)
                                                <div>
                                                    <p class="font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">Después</p>
                                                    <pre class="mt-0.5 whitespace-pre-wrap break-all rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-2 py-1 font-mono text-body-sm text-on-surface-variant">{{ json_encode($log->datos_nuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                @else
                                    <span class="text-outline">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center font-body-sm text-body-sm text-on-surface-variant">
                                Este usuario aún no tiene registros de auditoría.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="mt-4">{{ $logs->links() }}</div>
        @endif
    </x-card>

    {{-- Volver al detalle del usuario --}}
    <div class="flex justify-end">
        <a href="{{ route('admin.usuarios.show', $usuario) }}"
           class="inline-flex items-center rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">
            Volver al detalle
        </a>
    </div>

</div>
@endsection
