import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Carrito público: Alpine + localStorage, sin backend todavía.
 * El estado sobrevive al refresh en la clave `pixelstore_carrito`.
 */
const CARRITO_KEY = 'pixelstore_carrito';

const aNumero = (valor, porDefecto = 0) => {
    const numero = Number(valor);

    return Number.isFinite(numero) ? numero : porDefecto;
};

/**
 * El stock viaja con cada línea para poder topear la cantidad en el cliente.
 * Es un valor informativo, no una reserva: la autoridad real sigue siendo el
 * backend cuando el checkout exista.
 */
Alpine.data('carritoPublico', () => ({
    items: [],
    abierto: false,
    procesando: false,
    checkoutError: '',
    operacionId: '',

    init() {
        this.items = this.leerGuardado();
    },

    // --- Persistencia ---------------------------------------------------
    leerGuardado() {
        try {
            const guardado = JSON.parse(localStorage.getItem(CARRITO_KEY) ?? '[]');

            if (! Array.isArray(guardado)) {
                return [];
            }

            return guardado
                .map((item) => this.normalizar(item))
                .filter((item) => item.cantidad > 0);
        } catch {
            return [];
        }
    },

    guardar() {
        try {
            localStorage.setItem(CARRITO_KEY, JSON.stringify(this.items));
        } catch {
            // Modo privado o cuota llena: el carrito sigue vivo en memoria.
        }
    },

    normalizar(item) {
        const stock = Math.max(0, Math.trunc(aNumero(item?.stock)));
        const cantidad = Math.min(
            Math.max(1, Math.trunc(aNumero(item?.cantidad, 1))),
            Math.max(1, stock)
        );

        return {
            producto_id: Math.trunc(aNumero(item?.producto_id)),
            nombre: String(item?.nombre ?? ''),
            precio: Math.max(0, aNumero(item?.precio)),
            cantidad: stock > 0 ? cantidad : 0,
            imagen: item?.imagen ?? null,
            stock,
            slug: String(item?.slug ?? ''),
        };
    },

    // --- Mutaciones -----------------------------------------------------
    agregar(producto) {
        const stock = Math.max(0, Math.trunc(aNumero(producto?.stock)));

        if (stock <= 0) {
            return;
        }

        const id = Math.trunc(aNumero(producto?.producto_id));
        const existente = this.items.find((item) => item.producto_id === id);

        if (existente) {
            existente.stock = stock;
            existente.cantidad = Math.min(existente.cantidad + 1, stock);
        } else {
            this.items.push({
                producto_id: id,
                nombre: String(producto?.nombre ?? ''),
                precio: Math.max(0, aNumero(producto?.precio)),
                cantidad: 1,
                imagen: producto?.imagen ?? null,
                stock,
                slug: String(producto?.slug ?? ''),
            });
        }

        this.guardar();
    },

    quitar(index) {
        if (index < 0 || index >= this.items.length) {
            return;
        }

        this.items.splice(index, 1);
        this.guardar();
    },

    cambiarCantidad(index, delta) {
        const item = this.items[index];

        if (! item) {
            return;
        }

        const nueva = Math.trunc(aNumero(item.cantidad, 1)) + Math.trunc(aNumero(delta));

        if (nueva <= 0) {
            this.quitar(index);

            return;
        }

        item.cantidad = Math.min(nueva, Math.max(1, item.stock));
        this.guardar();
    },

    vaciar() {
        this.items = [];
        this.guardar();
    },

    // --- Lecturas -------------------------------------------------------
    get subtotal() {
        return this.items.reduce((suma, item) => suma + item.precio * item.cantidad, 0);
    },

    get totalItems() {
        return this.items.reduce((suma, item) => suma + item.cantidad, 0);
    },

    // --- Checkout -------------------------------------------------------
    async confirmarPedido(form) {
        if (this.procesando || this.items.length === 0) {
            return;
        }

        this.procesando = true;
        this.checkoutError = '';

        // Se reusa en reintentos para que el servidor responda idempotente.
        this.operacionId = this.operacionId
            || (globalThis.crypto?.randomUUID?.() ?? String(Date.now()));

        const datos = Object.fromEntries(new FormData(form).entries());
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        try {
            const respuesta = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    ...datos,
                    operacion_id: this.operacionId,
                    items: this.items.map((item) => ({
                        producto_id: item.producto_id,
                        cantidad: item.cantidad,
                    })),
                }),
            });

            const cuerpo = await respuesta.json().catch(() => ({}));

            if (! respuesta.ok) {
                this.checkoutError = cuerpo.message
                    ?? 'No pudimos registrar tu pedido. Intentá de nuevo.';

                return;
            }

            this.items = [];
            this.guardar();
            window.location.assign(cuerpo.redirect);
        } catch {
            this.checkoutError = 'No pudimos conectar con la tienda. Revisá tu conexión e intentá de nuevo.';
        } finally {
            this.procesando = false;
        }
    },

    cantidadDe(productoId) {
        const id = Math.trunc(aNumero(productoId));
        const item = this.items.find((linea) => linea.producto_id === id);

        return item ? item.cantidad : 0;
    },

    formato(valor) {
        return 'Bs ' + new Intl.NumberFormat('es-BO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(aNumero(valor));
    },
}));

Alpine.start();
