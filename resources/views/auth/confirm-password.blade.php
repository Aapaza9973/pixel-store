<x-guest-layout>
    <h1 class="font-display text-2xl font-bold text-white mb-3">
        {{ __('Confirmar contraseña') }}
    </h1>

    <div class="mb-6 text-sm text-slate-400">
        {{ __('Esta es un área segura de la aplicación. Confirma tu contraseña antes de continuar.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        {{-- Contraseña --}}
        <div>
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                {{ __('Confirmar') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
