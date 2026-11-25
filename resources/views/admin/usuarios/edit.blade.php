@extends('layouts.app')

@section('title', 'Editar usuario')
@section('subtitle', $usuario->name)

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Datos personales --}}
        <x-card title="Datos personales">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Nombre completo" />
                    <x-text-input id="name" name="name" type="text" :value="old('name', $usuario->name)" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="email" value="Correo electrónico" />
                    <x-text-input id="email" name="email" type="email" :value="old('email', $usuario->email)" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('email')" />
                </div>

                <div>
                    <x-input-label for="telefono" value="Teléfono" />
                    <x-text-input id="telefono" name="telefono" type="text" :value="old('telefono', $usuario->telefono)" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('telefono')" />
                </div>

                <div>
                    <x-input-label for="nit_ci" value="NIT / CI" />
                    <x-text-input id="nit_ci" name="nit_ci" type="text" :value="old('nit_ci', $usuario->nit_ci)" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('nit_ci')" />
                </div>
            </div>
        </x-card>

        {{-- Contraseña (opcional) --}}
        <x-card title="Cambiar contraseña" subtitle="Dejá estos campos vacíos para mantener la contraseña actual.">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-input-label for="password" value="Nueva contraseña" />
                    <x-text-input id="password" name="password" type="password" class="mt-1.5 block w-full" />
                    <x-input-error :messages="$errors->get('password')" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1.5 block w-full" />
                </div>
            </div>
        </x-card>

        {{-- Roles --}}
        <x-card title="Roles">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                @foreach ($roles as $rol)
                    <label class="flex cursor-pointer items-center gap-3 rounded-obsidian border border-outline-variant/30 bg-surface-container-low/50 p-3 transition hover:border-primary-container/40">
                        <input type="checkbox" name="roles[]" value="{{ $rol->name }}"
                               {{ in_array($rol->name, old('roles', $rolesAsignados), true) ? 'checked' : '' }}
                               class="rounded border-outline-variant/40 bg-surface-container-low text-primary-container focus:ring-primary-container/40">
                        <span class="font-body-md text-body-md text-on-surface">{{ $rol->name }}</span>
                    </label>
                @endforeach
            </div>

            <x-input-error :messages="$errors->get('roles')" />
        </x-card>

        {{-- Estado --}}
        <x-card>
            <label class="inline-flex cursor-pointer items-center gap-3">
                <input type="checkbox" name="activo" value="1" {{ old('activo', $usuario->activo) ? 'checked' : '' }}
                       class="rounded border-outline-variant/40 bg-surface-container-low text-primary-container focus:ring-primary-container/40">
                <span class="font-body-md text-body-md text-on-surface-variant">Cuenta activa</span>
            </label>
        </x-card>

        {{-- Botones --}}
        <div class="flex items-center gap-3">
            <x-primary-button>Actualizar usuario</x-primary-button>
            <x-secondary-button type="button" onclick="window.location='{{ route('admin.usuarios.index') }}'">
                Cancelar
            </x-secondary-button>
        </div>
    </form>
</div>
@endsection
