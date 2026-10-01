@extends('layouts.app')

@section('title', 'Historial de accesos')
@section('subtitle', 'Auditoría del usuario ' . $usuario->name)

@section('content')
<div class="space-y-6">

    {{-- Ficha del usuario auditado --}}
    <x-card>
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                {{ strtoupper(substr($usuario->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-white font-semibold">{{ $usuario->name }}</p>
                <p class="text-xs text-slate-500">{{ $usuario->email }}</p>
                <div class="flex flex-wrap gap-1 mt-1">
                    @forelse ($usuario->roles as $rol)
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-md bg-blue-500/10 text-blue-400">
                            {{ $rol->name }}
                        </span>
                    @empty
                        <span class="text-xs text-slate-500">Sin rol</span>
                    @endforelse
                </div>
            </div>
            <div class="ml-auto">
                <span class="text-xs text-slate-500">
                    Total:
                    <span class="text-white font-semibold">{{ $logs->total() }}</span>
                    eventos
                </span>
            </div>
        </div>
    </x-card>

    {{-- Registros de auditoría --}}
    <x-card title="Registro de auditoría" subtitle="Últimos 20 eventos por página">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-3 py-3">Fecha</th>
                        <th class="px-3 py-3">Acción</th>
                        <th class="px-3 py-3">IP</th>
                        <th class="px-3 py-3">Navegador</th>
                        <th class="px-3 py-3">Datos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-3 py-3 text-slate-400 text-xs whitespace-nowrap">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-3 py-3">
                                @php
                                    $badge = match ($log->accion) {
                                        'login' => 'bg-emerald-500/10 text-emerald-400',
                                        'logout' => 'bg-slate-500/10 text-slate-400',
                                        'login_fallido' => 'bg-red-500/10 text-red-400',
                                        'crear_user' => 'bg-blue-500/10 text-blue-400',
                                        'editar_user' => 'bg-amber-500/10 text-amber-400',
                                        'eliminar_user' => 'bg-red-500/10 text-red-400',
                                        'cambiar_roles_user' => 'bg-purple-500/10 text-purple-400',
                                        'acceso_denegado_sin_rol' => 'bg-red-500/10 text-red-400',
                                        default => 'bg-slate-500/10 text-slate-400',
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-md {{ $badge }}">
                                    {{ $log->accion }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-slate-400 font-mono text-xs">{{ $log->ip ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-500 text-xs max-w-[220px] truncate"
                                title="{{ $log->user_agent }}">
                                {{ $log->user_agent ?? '—' }}
                            </td>
                            <td class="px-3 py-3">
                                @if ($log->datos_anteriores !== null || $log->datos_nuevos !== null)
                                    <details>
                                        <summary class="cursor-pointer text-xs text-blue-400 hover:text-blue-300 select-none">
                                            Ver datos
                                        </summary>
                                        <div class="mt-2 space-y-2 max-w-md">
                                            @if ($log->datos_anteriores !== null)
                                                <div>
                                                    <p class="text-[11px] uppercase tracking-wider text-slate-500">Antes</p>
                                                    <pre class="mt-0.5 px-2 py-1 bg-slate-800 border border-slate-700 rounded text-xs text-slate-300 whitespace-pre-wrap break-all">{{ json_encode($log->datos_anteriores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            @endif
                                            @if ($log->datos_nuevos !== null)
                                                <div>
                                                    <p class="text-[11px] uppercase tracking-wider text-slate-500">Después</p>
                                                    <pre class="mt-0.5 px-2 py-1 bg-slate-800 border border-slate-700 rounded text-xs text-slate-300 whitespace-pre-wrap break-all">{{ json_encode($log->datos_nuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                @else
                                    <span class="text-xs text-slate-600">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-12 text-center text-sm text-slate-500">
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
           class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
            Volver al detalle
        </a>
    </div>

</div>
@endsection
