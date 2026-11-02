@extends('layouts.app')

@section('title', $cliente->nombre)
@section('subtitle', 'Detalle del cliente')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                Cliente #{{ $cliente->id }}
            </p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">{{ $cliente->nombre }}</h2>
        </div>

        <div class="flex items-center gap-2">
            @can('editar clientes')
                <a href="{{ route('admin.clientes.edit', $cliente) }}"
                   class="rounded-obsidian-lg bg-primary-container px-4 py-2.5 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                    Editar
                </a>
            @endcan
            <a href="{{ route('admin.clientes.index') }}"
               class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 text-body-md text-on-surface-variant transition hover:text-on-surface">
                Volver
            </a>
        </div>
    </div>

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
        <dl class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</dt>
                <dd class="mt-1">
                    @if ($cliente->activo)
                        <span class="inline-flex items-center gap-1.5 rounded-obsidian border border-emerald-500/25 bg-emerald-500/10 px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-emerald-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Activo
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-2 py-1 font-label-sm text-label-sm uppercase tracking-[0.14em] text-outline">
                            Inactivo
                        </span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">NIT / CI</dt>
                <dd class="mt-1 font-label-md text-label-md text-on-surface">{{ $cliente->nit_ci ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Teléfono</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $cliente->telefono ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Email</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $cliente->email ?? '—' }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Dirección</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $cliente->direccion ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Ventas</dt>
                <dd class="mt-1 font-headline-sm text-headline-sm tabular-nums text-on-surface">{{ $cliente->ventas_count }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Cliente frecuente</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">
                    {{ $cliente->es_frecuente ? 'Sí' : 'No' }}
                </dd>
            </div>
        </dl>
    </div>
</div>
@endsection
