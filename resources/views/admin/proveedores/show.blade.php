@extends('layouts.app')

@section('title', $proveedor->nombre)
@section('subtitle', 'Detalle del proveedor')

@section('content')
<div class="max-w-4xl space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                Proveedor #{{ $proveedor->id }}
            </p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">{{ $proveedor->nombre }}</h2>
        </div>

        <div class="flex items-center gap-2">
            @can('editar proveedores')
                <a href="{{ route('admin.proveedores.edit', $proveedor) }}"
                   class="rounded-obsidian-lg bg-primary-container px-4 py-2.5 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                    Editar
                </a>
            @endcan
            <a href="{{ route('admin.proveedores.index') }}"
               class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 text-body-md text-on-surface-variant transition hover:text-on-surface">
                Volver
            </a>
        </div>
    </div>

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
        <dl class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Tipo</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $proveedor->tipo }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Estado</dt>
                <dd class="mt-1">
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
                </dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">NIT</dt>
                <dd class="mt-1 font-label-md text-label-md text-on-surface">{{ $proveedor->nit ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Contacto</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $proveedor->contacto ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Teléfono</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $proveedor->telefono ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Email</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $proveedor->email ?? '—' }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Dirección</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $proveedor->direccion ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
        <h3 class="font-headline-sm text-headline-sm text-on-surface">Condiciones comerciales</h3>

        <dl class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Plazos de pago</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $proveedor->plazos_pago ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Tiempo de entrega</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface">{{ $proveedor->tiempo_entrega ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Descuento habitual</dt>
                <dd class="mt-1 font-label-md text-label-md tabular-nums text-on-surface">
                    {{ number_format((float) $proveedor->descuento_habitual, 2, ',', '.') }} %
                </dd>
            </div>
            <div class="md:col-span-3">
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Condiciones</dt>
                <dd class="mt-1 font-body-md text-body-md text-on-surface-variant">{{ $proveedor->condiciones ?? 'Sin condiciones registradas' }}</dd>
            </div>
            <div>
                <dt class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">Órdenes de compra</dt>
                <dd class="mt-1 font-headline-sm text-headline-sm tabular-nums text-on-surface">{{ $proveedor->ordenes_compra_count }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
