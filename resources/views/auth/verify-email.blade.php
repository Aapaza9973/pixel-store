<x-guest-layout>
    <h1 class="font-display text-2xl font-bold text-white mb-3">
        {{ __('Verificar correo electrónico') }}
    </h1>

    <div class="mb-6 text-sm text-slate-400">
        {{ __('¡Gracias por registrarte! Antes de empezar, verifica tu correo electrónico haciendo clic en el enlace que te enviamos. Si no lo recibiste, con gusto te enviaremos otro.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-emerald-400">
            {{ __('Se envió un nuevo enlace de verificación al correo electrónico que indicaste en el registro.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button>
                {{ __('Reenviar correo de verificación') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm text-slate-400 hover:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>
</x-guest-layout>
