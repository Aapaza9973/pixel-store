@props(['lifetime' => 30])

<div x-data="sessionTimeout({{ $lifetime }})"
     x-init="init()"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
     x-show="warningVisible"
     x-transition.opacity
     style="display: none;">

    <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-white font-semibold">Sesión por expirar</h3>
                <p class="text-sm text-slate-400 mt-1">
                    Su sesión expirará en <span class="text-amber-400 font-semibold" x-text="secondsLeft"></span> segundos por inactividad.
                </p>
                <div class="flex gap-2 mt-4">
                    <button @click="extend()"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                        Extender sesión
                    </button>
                    <button @click="logout()"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                        Cerrar sesión
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function sessionTimeout(lifetimeMinutes) {
    return {
        warningVisible: false,
        secondsLeft: 120,
        timer: null,
        warningTimer: null,

        init() {
            const totalMs = lifetimeMinutes * 60 * 1000;
            const warningMs = totalMs - (2 * 60 * 1000); // 2 min antes

            this.warningTimer = setTimeout(() => this.showWarning(), warningMs);

            ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
                document.addEventListener(event, () => this.reset(), { passive: true });
            });
        },

        reset() {
            if (this.warningVisible) return;
            clearTimeout(this.warningTimer);
            const totalMs = {{ $lifetime }} * 60 * 1000;
            this.warningTimer = setTimeout(() => this.showWarning(), totalMs - 120000);
        },

        showWarning() {
            this.warningVisible = true;
            this.secondsLeft = 120;
            this.timer = setInterval(() => {
                this.secondsLeft--;
                if (this.secondsLeft <= 0) this.logout();
            }, 1000);
        },

        extend() {
            fetch('{{ route('session.extend') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Content-Type': 'application/json',
                },
            }).then(() => this.reset())
              .then(() => {
                  this.warningVisible = false;
                  clearInterval(this.timer);
              });
        },

        logout() {
            document.querySelector('form[action="{{ route('logout') }}"]').submit();
        }
    }
}
</script>


