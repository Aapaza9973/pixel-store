<x-guest-layout>
    <h1 class="mb-3 font-headline-md text-headline-md text-on-surface">
        {{ __('Verificar correo electrónico') }}
    </h1>

    <div class="mb-6 font-body-md text-body-md text-on-surface-variant">
        {{ __('¡Gracias por registrarte! Antes de empezar, verifica tu correo electrónico haciendo clic en el enlace que te enviamos. Si no lo recibiste, con gusto te enviaremos otro.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-obsidian border border-emerald-500/25 bg-emerald-500/10 px-3 py-2 font-body-sm text-body-sm text-emerald-400">
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

            <button type="submit" class="rounded-obsidian font-body-md text-body-md text-outline transition hover:text-on-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60">
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>
</x-guest-layout>
