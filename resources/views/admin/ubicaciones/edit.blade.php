@extends('layouts.app')

@section('title', 'Editar ubicación')
@section('subtitle', $ubicacion->nombre_completo)

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.ubicaciones.update', $ubicacion) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card title="Datos de la ubicación">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="nombre" value="Nombre" />
                    <x-text-input id="nombre" name="nombre" type="text" :value="old('nombre', $ubicacion->nombre)" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('nombre')" />
                </div>

                <div>
                    <x-input-label for="tipo" value="Tipo" />
                    <select name="tipo" id="tipo"
                            class="mt-1.5 w-full rounded-obsidian border border-outline-variant/30 bg-surface-container-low px-3 py-2 text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/25">
                        <option value="tienda" @selected(old('tipo', $ubicacion->tipo) === 'tienda')>Tienda</option>
                        <option value="deposito" @selected(old('tipo', $ubicacion->tipo) === 'deposito')>Depósito</option>
                    </select>
                    <x-input-error :messages="$errors->get('tipo')" />
                </div>

                <div class="md:col-span-2">
                    <x-input-label for="direccion" value="Dirección" />
                    <x-text-input id="direccion" name="direccion" type="text" :value="old('direccion', $ubicacion->direccion)" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('direccion')" />
                </div>

                <div>
                    <x-input-label for="pasillo" value="Pasillo" />
                    <x-text-input id="pasillo" name="pasillo" type="text" :value="old('pasillo', $ubicacion->pasillo)" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('pasillo')" />
                </div>

                <div>
                    <x-input-label for="estante" value="Estante" />
                    <x-text-input id="estante" name="estante" type="text" :value="old('estante', $ubicacion->estante)" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('estante')" />
                </div>

                <div>
                    <x-input-label for="anaquel" value="Anaquel" />
                    <x-text-input id="anaquel" name="anaquel" type="text" :value="old('anaquel', $ubicacion->anaquel)" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('anaquel')" />
                </div>
            </div>

            <label class="mt-5 inline-flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="activa" value="1" {{ old('activa', $ubicacion->activa) ? 'checked' : '' }}
                       class="rounded border-outline-variant/40 bg-surface-container-low text-primary-container focus:ring-primary-container/40">
                <span class="font-body-sm text-body-sm text-on-surface-variant">Ubicación activa</span>
            </label>
        </x-card>

        <div class="flex items-center gap-3">
            <x-primary-button>Actualizar ubicación</x-primary-button>
            <x-secondary-button type="button" onclick="window.location='{{ route('admin.ubicaciones.index') }}'">
                Cancelar
            </x-secondary-button>
        </div>
    </form>
</div>
@endsection
