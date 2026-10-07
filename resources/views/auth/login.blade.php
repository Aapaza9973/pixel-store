<x-guest-layout>
    <div class="mb-6 text-center">
        {{-- Logo --}}
        <a href="/" class="inline-flex items-center justify-center">
            <img src="{{ asset('images/logo/pixel-logo-horizontal.png') }}"
                 alt="Pixel Store"
                 class="h-16 w-auto">
        </a>
        <p class="mt-3 text-sm text-slate-400">
            Todo el mundo tecnológico, pixel a pixel
        </p>
    </div>

    <h1 class="font-display text-2xl font-bold text-white text-center mb-6">
        {{ __('Iniciar sesión') }}
    </h1>

    {{-- Estado de la sesión --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Correo electrónico --}}
        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Contraseña --}}
        <div class="mt-4">
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Recordarme --}}
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-700 bg-slate-800 text-blue-600 shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900" name="remember">
                <span class="ms-2 text-sm text-slate-400">{{ __('Recordarme') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="text-sm text-blue-400 hover:text-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif

            <x-primary-button class="ms-4">
                {{ __('Iniciar sesión') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
