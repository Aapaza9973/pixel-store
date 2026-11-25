<x-guest-layout>
    <h1 class="mb-3 font-headline-md text-headline-md text-on-surface">
        {{ __('Confirmar contraseña') }}
    </h1>

    <div class="mb-6 font-body-md text-body-md text-on-surface-variant">
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
