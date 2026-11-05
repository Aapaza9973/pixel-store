@extends('layouts.app')

@section('title', 'Nuevo cliente')
@section('subtitle', 'Alta de cliente')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                Ventas
            </p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">
                Nuevo cliente
            </h2>
        </div>
        <a href="{{ route('admin.clientes.index') }}"
           class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2 text-body-md text-on-surface-variant transition hover:text-on-surface">
            Volver
        </a>
    </div>

    <form method="POST" action="{{ route('admin.clientes.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Datos del cliente</h3>

            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="nombre" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Nombre <span class="text-error">*</span>
                    </label>
                    <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    @error('nombre') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="nit_ci" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        NIT / CI
                    </label>
                    <input type="text" name="nit_ci" id="nit_ci" value="{{ old('nit_ci') }}"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    @error('nit_ci') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="telefono" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Teléfono
                    </label>
                    <input type="text" name="telefono" id="telefono" value="{{ old('telefono') }}"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    @error('telefono') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="email" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Email
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    @error('email') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="direccion" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Dirección
                    </label>
                    <input type="text" name="direccion" id="direccion" value="{{ old('direccion') }}"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    @error('direccion') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="mt-5 inline-flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', true))
                       class="rounded border-outline-variant/40 bg-surface-container-low text-primary-container focus:ring-primary-container">
                <span class="font-body-md text-body-md text-on-surface-variant">Cliente activo</span>
            </label>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="rounded-obsidian-lg bg-primary-container px-4 py-2.5 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                Guardar cliente
            </button>
            <a href="{{ route('admin.clientes.index') }}"
               class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 text-body-md text-on-surface-variant transition hover:text-on-surface">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
