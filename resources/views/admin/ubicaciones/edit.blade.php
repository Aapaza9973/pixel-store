@extends('layouts.app')

@section('title', 'Editar ubicación')
@section('subtitle', $ubicacion->nombre_completo)

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.ubicaciones.update', $ubicacion) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-5">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">Datos de la ubicación</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="nombre" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Nombre <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $ubicacion->nombre) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('nombre') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tipo" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Tipo <span class="text-red-400">*</span>
                    </label>
                    <select name="tipo" id="tipo"
                            class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <option value="tienda" @selected(old('tipo', $ubicacion->tipo) === 'tienda')>Tienda</option>
                        <option value="deposito" @selected(old('tipo', $ubicacion->tipo) === 'deposito')>Depósito</option>
                    </select>
                    @error('tipo') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="direccion" class="block text-sm font-medium text-slate-300 mb-1.5">Dirección</label>
                    <input type="text" name="direccion" id="direccion" value="{{ old('direccion', $ubicacion->direccion) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('direccion') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="pasillo" class="block text-sm font-medium text-slate-300 mb-1.5">Pasillo</label>
                    <input type="text" name="pasillo" id="pasillo" value="{{ old('pasillo', $ubicacion->pasillo) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('pasillo') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="estante" class="block text-sm font-medium text-slate-300 mb-1.5">Estante</label>
                    <input type="text" name="estante" id="estante" value="{{ old('estante', $ubicacion->estante) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('estante') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="anaquel" class="block text-sm font-medium text-slate-300 mb-1.5">Anaquel</label>
                    <input type="text" name="anaquel" id="anaquel" value="{{ old('anaquel', $ubicacion->anaquel) }}"
                           class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    @error('anaquel') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="activa" value="1" {{ old('activa', $ubicacion->activa) ? 'checked' : '' }}
                       class="rounded bg-slate-800 border-slate-700 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-slate-300">Ubicación activa</span>
            </label>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                Actualizar ubicación
            </button>
            <a href="{{ route('admin.ubicaciones.index') }}"
               class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
