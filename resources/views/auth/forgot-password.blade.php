<x-guest-layout>
    <h1 class="mb-3 font-headline-md text-headline-md text-on-surface">
        {{ __('Recuperar contraseña') }}
    </h1>

    <div class="mb-6 font-body-md text-body-md text-on-surface-variant">
        {{ __('¿Olvidaste tu contraseña? No hay problema. Indícanos tu correo electrónico y te enviaremos un enlace para restablecerla y elegir una nueva.') }}
    </div>

    {{-- Estado de la sesión --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        {{-- Correo electrónico --}}
        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Enviar enlace de restablecimiento') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
