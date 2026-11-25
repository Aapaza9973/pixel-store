<section>
    <header>
        <h2 class="font-headline-sm text-headline-sm text-on-surface">
            {{ __('Información del perfil') }}
        </h2>

        <p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">
            {{ __('Actualiza la información de tu cuenta y tu correo electrónico.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Nombre')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">
                        {{ __('Tu correo electrónico no está verificado.') }}

                        <button form="send-verification" class="rounded-obsidian text-secondary underline transition hover:text-on-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container/60">
                            {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-body-sm text-body-sm text-emerald-400">
                            {{ __('Se envió un nuevo enlace de verificación a tu correo electrónico.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Guardar') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="font-body-sm text-body-sm text-emerald-400"
                >{{ __('Guardado.') }}</p>
            @endif
        </div>
    </form>
</section>
