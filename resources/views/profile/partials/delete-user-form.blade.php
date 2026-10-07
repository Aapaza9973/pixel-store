<section class="space-y-6">
    <header>
        <h2 class="font-display text-lg font-semibold text-white">
            {{ __('Eliminar cuenta') }}
        </h2>

        <p class="mt-1 text-sm text-slate-400">
            {{ __('Una vez eliminada tu cuenta, todos sus recursos y datos se borrarán de forma permanente. Antes de eliminarla, descarga cualquier dato o información que quieras conservar.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Eliminar cuenta') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="font-display text-lg font-semibold text-white">
                {{ __('¿Seguro que quieres eliminar tu cuenta?') }}
            </h2>

            <p class="mt-1 text-sm text-slate-400">
                {{ __('Una vez eliminada tu cuenta, todos sus recursos y datos se borrarán de forma permanente. Ingresa tu contraseña para confirmar que quieres eliminarla definitivamente.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Contraseña') }}" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Contraseña') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('Eliminar cuenta') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
