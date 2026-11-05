@extends('layouts.app')

@section('title', 'Proveedores')
@section('subtitle', 'Compras y abastecimiento')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                Compras
            </p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">
                Proveedores
            </h2>
            <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
                {{ $proveedores->total() }} proveedor(es) registrados
            </p>
        </div>

        @can('crear proveedores')
            <a href="{{ route('admin.proveedores.create') }}"
               class="inline-flex items-center gap-2 rounded-obsidian-lg bg-primary-container px-4 py-2.5 text-body-md font-medium text-on-primary-container shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_0_20px_rgba(37,99,235,0.35)] transition hover:bg-primary-container/90">
                Nuevo proveedor
            </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low/40 p-4">
        <form method="GET" action="{{ route('admin.proveedores.index') }}"
              class="flex flex-wrap items-end gap-3">
            <div class="min-w-[220px] flex-1">
                <label for="buscar" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    Buscar
                </label>
                <input type="text" name="buscar" id="buscar" value="{{ request('buscar') }}"
                       placeholder="Nombre, NIT, contacto o email"
                       class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
            </div>

            <div>
                <label for="tipo" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    Tipo
                </label>
                <select name="tipo" id="tipo"
                        class="rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    <option value="">Todos</option>
                    @foreach (['Mayorista', 'Importador', 'Distribuidor', 'Local'] as $tipo)
                        <option value="{{ $tipo }}" @selected(request('tipo') === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="estado" class="mb-1 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                    Estado
                </label>
                <select name="estado" id="estado"
                        class="rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    <option value="">Todos</option>
                    <option value="activos" @selected(request('estado') === 'activos')>Activos</option>
                    <option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="rounded-obsidian-lg bg-primary-container px-4 py-2 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                    Filtrar
                </button>
                <a href="{{ route('admin.proveedores.index') }}"
                   class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2 text-body-md text-on-surface-variant transition hover:text-on-surface">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="overflow-hidden rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-body-md">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Proveedor</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">NIT</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Contacto</th>
                        <th class="px-4 py-3 text-left font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Tipo</th>
                        <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Órdenes</th>
                        <th class="px-4 py-3 text-center font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</th>
                        <th class="px-4 py-3 text-right font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/15">
                    @forelse ($proveedores as $proveedor)
                        <tr class="transition hover:bg-primary-container/[0.04]">
                            <td class="px-4 py-3.5">
                                <a href="{{ route('admin.proveedores.show', $proveedor) }}"
                                   class="font-medium text-on-surface transition hover:text-secondary">
                                    {{ $proveedor->nombre }}
                                </a>
                                <p class="font-body-sm text-body-sm text-on-surface-variant">
                                    {{ $proveedor->email ?? 'Sin email' }}
                                </p>
                            </td>
                            <td class="px-4 py-3.5 font-label-md text-label-md text-on-surface-variant">
                                {{ $proveedor->nit ?? '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-on-surface-variant">
                                {{ $proveedor->contacto ?? '—' }}
                                @if ($proveedor->telefono)
                                    <span class="block font-label-sm text-label-sm text-outline">{{ $proveedor->telefono }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-on-surface-variant">
                                    {{ $proveedor->tipo }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-label-md text-label-md tabular-nums text-on-surface-variant">
                                {{ $proveedor->ordenes_compra_count }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($proveedor->activo)
                                    <span class="inline-flex items-center gap-1.5 rounded-obsidian border border-emerald-500/25 bg-emerald-500/10 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-emerald-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <div class="inline-flex items-center gap-3">
                                    <a href="{{ route('admin.proveedores.show', $proveedor) }}"
                                       class="text-body-sm text-on-surface-variant transition hover:text-on-surface">
                                        Ver
                                    </a>
                                    @can('editar proveedores')
                                        <a href="{{ route('admin.proveedores.edit', $proveedor) }}"
                                           class="text-body-sm text-secondary transition hover:text-on-surface">
                                            Editar
                                        </a>
                                    @endcan
                                    @can('eliminar proveedores')
                                        <form method="POST" action="{{ route('admin.proveedores.destroy', $proveedor) }}"
                                              onsubmit="return confirm('¿Eliminar el proveedor {{ $proveedor->nombre }}?')"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-body-sm text-error transition hover:text-error/80">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center font-body-sm text-body-sm text-on-surface-variant">
                                No hay proveedores que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($proveedores->hasPages())
            <div class="border-t border-outline-variant/15 px-4 py-3">
                {{ $proveedores->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
