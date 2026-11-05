@extends('layouts.app')

@section('title', 'Nuevo proveedor')
@section('subtitle', 'Alta de proveedor')

@section('content')
<div class="max-w-4xl space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                Compras
            </p>
            <h2 class="mt-1 font-headline-md text-headline-md text-on-surface">
                Nuevo proveedor
            </h2>
        </div>
        <a href="{{ route('admin.proveedores.index') }}"
           class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2 text-body-md text-on-surface-variant transition hover:text-on-surface">
            Volver
        </a>
    </div>

    <form method="POST" action="{{ route('admin.proveedores.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Datos generales</h3>

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
                    <label for="nit" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        NIT
                    </label>
                    <input type="text" name="nit" id="nit" value="{{ old('nit') }}"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    @error('nit') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tipo" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Tipo <span class="text-error">*</span>
                    </label>
                    <select name="tipo" id="tipo"
                            class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                        @foreach (['Mayorista', 'Importador', 'Distribuidor', 'Local'] as $tipo)
                            <option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                    @error('tipo') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contacto" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Persona de contacto
                    </label>
                    <input type="text" name="contacto" id="contacto" value="{{ old('contacto') }}"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    @error('contacto') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="telefono" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Teléfono
                    </label>
                    <input type="text" name="telefono" id="telefono" value="{{ old('telefono') }}"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    @error('telefono') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
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
        </div>

        <div class="rounded-obsidian-xl border border-outline-variant/30 bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
            <h3 class="font-headline-sm text-headline-sm text-on-surface">Condiciones comerciales</h3>

            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
                <div>
                    <label for="plazos_pago" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Plazos de pago
                    </label>
                    <input type="text" name="plazos_pago" id="plazos_pago" value="{{ old('plazos_pago') }}"
                           placeholder="Ej. 30 días"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    @error('plazos_pago') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tiempo_entrega" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Tiempo de entrega
                    </label>
                    <input type="text" name="tiempo_entrega" id="tiempo_entrega" value="{{ old('tiempo_entrega') }}"
                           placeholder="Ej. 5 días hábiles"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    @error('tiempo_entrega') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="descuento_habitual" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Descuento habitual (%)
                    </label>
                    <input type="number" step="0.01" min="0" max="100" name="descuento_habitual" id="descuento_habitual"
                           value="{{ old('descuento_habitual', 0) }}"
                           class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-label-md text-label-md tabular-nums text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                    @error('descuento_habitual') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-3">
                    <label for="condiciones" class="mb-1.5 block font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                        Condiciones
                    </label>
                    <textarea name="condiciones" id="condiciones" rows="3"
                              class="w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">{{ old('condiciones') }}</textarea>
                    @error('condiciones') <p class="mt-1 font-body-sm text-body-sm text-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="mt-5 inline-flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', true))
                       class="rounded border-outline-variant/40 bg-surface-container-low text-primary-container focus:ring-primary-container">
                <span class="font-body-md text-body-md text-on-surface-variant">Proveedor activo</span>
            </label>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="rounded-obsidian-lg bg-primary-container px-4 py-2.5 text-body-md font-medium text-on-primary-container transition hover:bg-primary-container/90">
                Guardar proveedor
            </button>
            <a href="{{ route('admin.proveedores.index') }}"
               class="rounded-obsidian-lg border border-outline-variant/30 bg-surface-container-low px-4 py-2.5 text-body-md text-on-surface-variant transition hover:text-on-surface">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
