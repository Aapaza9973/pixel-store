@extends('layouts.app')

@section('title', 'Auditoría')
@section('subtitle', 'Quién hizo qué y cuándo')

@section('content')
<div class="space-y-6">

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-5 backdrop-blur-xl">
        <form method="GET" action="{{ route('admin.auditoria.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="accion" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acción</label>
                <select name="accion" id="accion"
                        class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    <option value="">Todas</option>
                    @foreach ($acciones as $opcion)
                        <option value="{{ $opcion }}" {{ $filtros['accion'] === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="usuario" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Usuario</label>
                <select name="usuario" id="usuario"
                        class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
                    <option value="">Todos</option>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}" {{ $filtros['usuario'] === (string) $usuario->id ? 'selected' : '' }}>
                            {{ $usuario->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="desde" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Desde</label>
                <input type="date" name="desde" id="desde" value="{{ $filtros['desde'] }}"
                       class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
            </div>

            <div>
                <label for="hasta" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Hasta</label>
                <input type="date" name="hasta" id="hasta" value="{{ $filtros['hasta'] }}"
                       class="rounded-obsidian border-outline-variant/40 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="rounded-obsidian bg-primary-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-white transition hover:bg-primary-container/85">Filtrar</button>
                <a href="{{ route('admin.auditoria.index') }}" class="rounded-obsidian bg-surface-container px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface-variant transition hover:text-on-surface">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="font-body-sm text-body-sm text-on-surface-variant">
            Total: <span class="font-semibold text-on-surface">{{ $logs->total() }}</span> registros
        </p>
        <a href="{{ route('admin.auditoria.export', request()->only(['accion', 'usuario', 'desde', 'hasta'])) }}"
           class="rounded-obsidian-lg border border-outline-variant/40 px-4 py-2 font-label-md text-label-md uppercase tracking-[0.14em] text-on-surface transition hover:border-secondary hover:text-secondary">
            Exportar CSV
        </a>
    </div>

    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr class="text-left">
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Fecha</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Usuario</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acción</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Modelo</th>
                        <th class="px-4 py-3.5 font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-outline-variant/15 transition hover:bg-primary-container/[0.04] last:border-0">
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface-variant">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3.5 text-on-surface">{{ $log->user?->name ?? 'Sistema' }}</td>
                            <td class="px-4 py-3.5 font-mono text-body-sm text-secondary">{{ $log->accion }}</td>
                            <td class="px-4 py-3.5 text-on-surface-variant">
                                {{ $log->modelo ? class_basename($log->modelo) : '—' }}
                                @if ($log->modelo_id)
                                    <span class="text-outline">#{{ $log->modelo_id }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface-variant">{{ $log->ip ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center font-body-md text-body-md text-outline">No hay registros para esos filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="mt-4 px-5 pb-5">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
