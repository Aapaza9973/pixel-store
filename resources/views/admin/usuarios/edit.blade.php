@extends('layouts.app')

@section('title', 'Editar usuario')
@section('subtitle', $usuario->name)

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Datos personales --}}
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-5">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">
                Datos personales
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Nombre completo <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $usuario->name) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Correo electrónico <span class="text-red-400">*</span>
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email', $usuario->email) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('email') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="telefono" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Teléfono
                    </label>
                    <input type="text" name="telefono" id="telefono" value="{{ old('telefono', $usuario->telefono) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('telefono') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="nit_ci" class="block text-sm font-medium text-slate-300 mb-1.5">
                        NIT / CI
                    </label>
                    <input type="text" name="nit_ci" id="nit_ci" value="{{ old('nit_ci', $usuario->nit_ci) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('nit_ci') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Contraseña (opcional) --}}
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-5">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">
                Cambiar contraseña
            </h3>
            <p class="text-xs text-slate-500">
                Dejá estos campos vacíos para mantener la contraseña actual.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Nueva contraseña
                    </label>
                    <input type="password" name="password" id="password"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('password') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Confirmar contraseña
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
        </div>

        {{-- Roles --}}
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-5">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">
                Roles <span class="text-red-400">*</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                @foreach ($roles as $rol)
                    <label class="flex items-center gap-3 p-3 bg-slate-800/50 hover:bg-slate-800 rounded-lg cursor-pointer border border-slate-700 transition">
                        <input type="checkbox" name="roles[]" value="{{ $rol->name }}"
                               {{ in_array($rol->name, old('roles', $rolesAsignados), true) ? 'checked' : '' }}
                               class="rounded border-slate-600 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-slate-300 font-medium">{{ $rol->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('roles') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        {{-- Estado --}}
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6">
            <label class="inline-flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="activo" value="1" {{ old('activo', $usuario->activo) ? 'checked' : '' }}
                       class="rounded bg-slate-800 border-slate-700 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-slate-300">Cuenta activa</span>
            </label>
        </div>

        {{-- Botones --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-full transition">
                Actualizar usuario
            </button>
            <a href="{{ route('admin.usuarios.index') }}"
               class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection

