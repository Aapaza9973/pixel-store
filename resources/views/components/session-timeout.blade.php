@props(['lifetime' => 30])

<div
    x-data="sessionTimeout"
    data-lifetime="{{ (int) $lifetime }}"
    x-show="warningVisible"
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-labelledby="session-timeout-title"
>
    <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 id="session-timeout-title" class="text-white font-semibold">Sesión por expirar</h3>
                <p class="text-sm text-slate-400 mt-1">
                    Su sesión expirará en <span class="text-amber-400 font-semibold" x-text="secondsLeft"></span> segundos por inactividad.
                </p>
                <div class="flex gap-2 mt-4">
                    <button @click="extend()"
                            :disabled="extending"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium rounded-lg transition">
                        <span x-show="! extending">Extender sesión</span>
                        <span x-show="extending" x-cloak>Extendiendo…</span>
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
document.addEventListener('alpine:init', () => {
    Alpine.data('sessionTimeout', () => ({
        warningVisible: false,
        extending: false,
        secondsLeft: 0,

        lifetimeMs: 0,
        warningLeadMs: 0,
        warningTimer: null,
        countdownTimer: null,
        lastActivityAt: 0,
        throttled: false,
        activityEvents: ['click', 'keydown', 'scroll', 'mousemove', 'touchstart'],

        init() {
            const minutes = parseInt(this.$el.dataset.lifetime, 10) || 30;
            this.lifetimeMs = minutes * 60 * 1000;

            // Avisar 2 minutos antes, pero nunca más de la mitad del tiempo total.
            // Así con sesiones de prueba de 1 minuto el aviso aparece a los 30s.
            this.warningLeadMs = Math.min(2 * 60 * 1000, Math.floor(this.lifetimeMs / 2));

            this.onActivity = this.onActivity.bind(this);
            this.activityEvents.forEach((event) => {
                document.addEventListener(event, this.onActivity, { passive: true });
            });

            // Limpieza al salir de la página. Devuelve undefined a propósito para
            // NO activar el diálogo nativo de "¿abandonar el sitio?".
            window.addEventListener('beforeunload', () => this.destroy());

            this.scheduleWarning();
        },

        destroy() {
            clearTimeout(this.warningTimer);
            clearInterval(this.countdownTimer);
            this.activityEvents.forEach((event) => {
                document.removeEventListener(event, this.onActivity);
            });
        },

        onActivity() {
            if (this.warningVisible) {
                return; // Mientras el aviso esté visible solo cuentan Extender o Cerrar sesión.
            }

            // Throttle: mousemove/scroll disparan constantemente. Recrear el
            // setTimeout en cada evento es innecesario y costoso.
            if (this.throttled) {
                return;
            }
            this.throttled = true;
            setTimeout(() => { this.throttled = false; }, 1000);

            this.scheduleWarning();
        },

        scheduleWarning() {
            clearTimeout(this.warningTimer);
            const delay = Math.max(this.lifetimeMs - this.warningLeadMs, 0);
            this.warningTimer = setTimeout(() => this.showWarning(), delay);
        },

        showWarning() {
            if (this.warningVisible) {
                return;
            }

            this.warningVisible = true;
            this.secondsLeft = Math.max(Math.floor(this.warningLeadMs / 1000), 1);

            clearInterval(this.countdownTimer);
            this.countdownTimer = setInterval(() => {
                this.secondsLeft -= 1;
                if (this.secondsLeft <= 0) {
                    this.logout();
                }
            }, 1000);
        },

        extend() {
            if (this.extending) {
                return;
            }
            this.extending = true;

            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            fetch('{{ route('session.extend') }}', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
            })
                .then((response) => {
                    // 401/419/302 significan que la sesión ya no es válida en el
                    // servidor: no tiene sentido "extenderla", hay que re-loguear.
                    if (! response.ok) {
                        throw new Error('Sesión no válida');
                    }
                    return response.json();
                })
                .then(() => this.rearm())
                .catch(() => this.goToLogin());
        },

        // Rearma el temporizador tras extender la sesión. Importante: primero
        // ocultamos el aviso, si no `onActivity` deja de reprogramar y la
        // sesión volvería a expirar en silencio.
        rearm() {
            clearInterval(this.countdownTimer);
            this.countdownTimer = null;
            this.warningVisible = false;
            this.extending = false;

            this.showToast('Sesión extendida correctamente.');

            this.scheduleWarning();
        },

        logout() {
            clearInterval(this.countdownTimer);

            // Form de logout del layout. Si por lo que sea no existe (por
            // ejemplo, vista sin layout) construimos uno al vuelo.
            const form = document.querySelector('form[data-logout-form]');
            if (form) {
                form.submit();
                return;
            }

            const fallback = document.createElement('form');
            fallback.method = 'POST';
            fallback.action = '{{ route('logout') }}';
            fallback.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
            document.body.appendChild(fallback);
            fallback.submit();
        },

        goToLogin() {
            window.location.href = '{{ route('login') }}';
        },

        showToast(message) {
            // Ligero aviso no bloqueante; no depende de librerías externas.
            const toast = document.createElement('div');
            toast.textContent = message;
            toast.className = 'fixed bottom-6 right-6 z-[60] rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-lg';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        },
    }));
});
</script>
