/**
 * Punto de Venta: estado Alpine del carrito, búsqueda de productos,
 * pagos mixtos y confirmación de la venta.
 *
 * Se registra en `window.carrito` desde resources/js/app.js y la vista lo
 * engancha con `x-data="carrito()"`.
 */
export default function crearCarrito() {
        return {
            paso: 1,
            avisoFlujo: null,
            items: [],
            pagos: [],
            nuevoPago: { metodo: 'Efectivo', monto: 0 },
            descuentoMaximo: 0,
            descuento: { tipo: '%', valor: 0 },
            storageKey: 'pos.carrito.v1',
            operacionKey: 'pos.operacion.v1',
            urlStore: '',
            enviando: false,
            errores: {},
            errorGeneral: null,
            ventaConfirmada: null,
            avisoStock: null,

            // HU-5.5: cliente de la venta.
            cliente: null,
            busquedaCliente: '',
            resultadosCliente: [],
            buscandoCliente: false,
            modalCliente: false,
            guardandoCliente: false,
            nuevoCliente: { nombre: '', telefono: '', nit_ci: '', email: '' },
            errorCliente: null,
            urlClientes: '',
            urlClienteStore: '',

            // HU-5.11: autorización de descuentos por un administrador.
            urlAutorizarDescuento: '',
            autorizacionId: null,
            autorizacionAdmin: null,
            modalAutorizacion: {
                abierto: false,
                email: '',
                password: '',
                motivo: '',
                enviando: false,
                error: null,
                errores: {},
            },

            // HU-5.3: buscador de productos del POS.
            urlBuscarProductos: '',
            urlComprobanteBase: '',
            busquedaProducto: '',
            resultadosProducto: [],
            buscandoProducto: false,
            errorProducto: null,

            // HU-5.6 / 5.7.3: número de serie por línea y validación previa.
            urlSeries: '',
            urlValidarSerie: '',
            serieModal: { abierto: false, itemIndex: null, cargando: false, validando: false, series: [], error: null },
            silenciarBanner: false,

            init() {
                // Límite de descuento del rol, inyectado desde Blade.
                this.descuentoMaximo = Number(this.$el.dataset.descuentoMaximo) || 0;
                this.urlStore = this.$el.dataset.urlStore || '';
                this.urlClientes = this.$el.dataset.urlClientes || '';
                this.urlClienteStore = this.$el.dataset.urlClienteStore || '';
                this.urlSeries = this.$el.dataset.urlSeries || '';
                this.urlValidarSerie = this.$el.dataset.urlValidarSerie || '';
                this.urlBuscarProductos = this.$el.dataset.urlBuscarProductos || '';
                this.urlComprobanteBase = this.$el.dataset.urlComprobante || '';
                this.urlAutorizarDescuento = this.$el.dataset.urlAutorizarDescuento || '';
                this.operacionId = this.operacionActual();

                this.cargar();

                // deep: las filas de pago y de carrito se editan en el lugar.
                // La autorización es por (operacion, porcentaje, monto); si cambia
                // el carrito el monto cambia y el servidor la rechazaría.
                this.$watch('items', () => {
                    this.persistir();
                    this.limpiarBanner();
                    this.limpiarAutorizacion();
                }, { deep: true });

                this.$watch('pagos', () => {
                    this.persistir();
                    this.limpiarBanner();
                }, { deep: true });

                // La autorización vale para un monto/porcentaje concretos: si el
                // descuento cambia, hay que volver a pedirla.
                this.$watch('descuento', () => {
                    this.limpiarAutorizacion();
                    this.persistir();
                }, { deep: true });
            },

            get subtotal() {
                return this.items.reduce((suma, item) => suma + (Number(item.subtotal) || 0), 0);
            },

            get descuentoMonto() {
                const valor = Number(this.descuento.valor) || 0;

                if (valor <= 0) {
                    return 0;
                }

                return this.descuento.tipo === '%'
                    ? this.subtotal * (valor / 100)
                    : valor;
            },

            get descuentoPorcentaje() {
                return this.subtotal > 0 ? (this.descuentoMonto / this.subtotal) * 100 : 0;
            },

            get descuentoExcedeLimite() {
                return this.descuentoPorcentaje > this.descuentoMaximo + 0.0001;
            },

            // Sin autorización de admin (HU-5.11) el descuento no se aplica; con
            // autorización registrada sí, para que el total y el vuelto sean los
            // que el servidor va a confirmar.
            get descuentoAplicado() {
                if (! this.descuentoExcedeLimite) {
                    return this.descuentoMonto;
                }

                return this.autorizacionId ? this.descuentoMonto : 0;
            },

            get total() {
                return Math.max(this.subtotal - this.descuentoAplicado, 0);
            },

            get totalPagado() {
                return this.pagos.reduce((suma, pago) => suma + (Number(pago.monto) || 0), 0);
            },

            get saldoPendiente() {
                return Math.max(this.total - this.totalPagado, 0);
            },

            // Comparación en centavos: evita falsos pendientes por redondeo.
            get faltaPago() {
                return Math.round(this.saldoPendiente * 100) > 0;
            },

            // El servidor recibe porcentaje, nunca monto.
            get porcentajeParaEnviar() {
                const valor = Number(this.descuento.valor) || 0;

                if (valor <= 0 || this.subtotal <= 0) {
                    return 0;
                }

                const porcentaje = this.descuento.tipo === '%'
                    ? valor
                    : (valor / this.subtotal) * 100;

                return Math.round(Math.min(Math.max(porcentaje, 0), 100) * 100) / 100;
            },

            get erroresCampos() {
                return Object.values(this.errores || {}).flat();
            },

            // 5.6.7: no se puede confirmar si una línea exige serie y no la tiene.
            get faltanSeries() {
                return this.items.some((item) => item.maneja_numero_serie && ! item.numero_serie_id);
            },

            // El banner se limpia al tocar el carrito, así que el link vive
            // dentro del x-if y desaparece con él.
            get urlComprobante() {
                if (! this.ventaConfirmada || ! this.urlComprobanteBase) {
                    return '#';
                }

                return this.urlComprobanteBase.replace('__venta__', this.ventaConfirmada.id);
            },

            irAPaso(paso) {
                if (paso === 2 && this.items.length === 0) {
                    this.avisoFlujo = 'Agregá al menos un producto para ver el carrito.';
                    this.paso = 1;
                    this.$nextTick(() => this.enfocarBuscador());
                    return;
                }

                if (paso === 3 && this.items.length === 0) {
                    this.avisoFlujo = 'Agregá al menos un producto antes de cobrar.';
                    this.paso = 1;
                    this.$nextTick(() => this.enfocarBuscador());
                    return;
                }

                this.avisoFlujo = null;
                this.paso = paso;

                if (paso === 1) {
                    this.$nextTick(() => this.enfocarBuscador());
                }
            },

            agregarItem(producto) {
                const existente = this.items.find((item) => item.producto_id === producto.producto_id);

                if (existente) {
                    this.cambiarCantidad(this.items.indexOf(existente), existente.cantidad + (Number(producto.cantidad) || 1));

                    return;
                }

                const cantidad = Math.max(Number(producto.cantidad) || 1, 1);
                const precio = Number(producto.precio) || 0;

                this.items.push({
                    producto_id: producto.producto_id,
                    nombre: producto.nombre,
                    precio: precio,
                    cantidad: cantidad,
                    subtotal: precio * cantidad,
                    stock: Number(producto.stock ?? 0),
                    maneja_numero_serie: Boolean(producto.maneja_numero_serie),
                    numero_serie_id: null,
                    numero_serie: null,
                });
            },

            eliminarItem(index) {
                this.items.splice(index, 1);
                this.avisoStock = null;
            },

            async buscarProductos() {
                const termino = (this.busquedaProducto || '').trim();
                this.errorProducto = null;

                // Mismo umbral que el buscador de clientes: no se consulta por
                // una sola letra.
                if (termino.length < 2) {
                    this.resultadosProducto = [];

                    return;
                }

                this.buscandoProducto = true;

                try {
                    const respuesta = await fetch(this.urlBuscarProductos + '?q=' + encodeURIComponent(termino), {
                        headers: { 'Accept': 'application/json' },
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (! respuesta.ok) {
                        this.resultadosProducto = [];
                        this.errorProducto = cuerpo.message || 'No se pudieron buscar productos.';

                        return;
                    }

                    this.resultadosProducto = cuerpo.data || [];
                } catch (error) {
                    this.resultadosProducto = [];
                    this.errorProducto = 'No se pudo conectar con el servidor.';
                } finally {
                    this.buscandoProducto = false;
                }
            },

            agregarProductoBuscado(producto) {
                if (! producto || Number(producto.stock) < 1) {
                    return;
                }

                // agregarItem ya incrementa la cantidad si el producto está en
                // el carrito, en vez de duplicar la fila.
                this.agregarItem({
                    producto_id: producto.id,
                    nombre: producto.nombre,
                    precio: producto.precio_unitario,
                    cantidad: 1,
                    maneja_numero_serie: producto.maneja_numero_serie,
                });

                this.busquedaProducto = '';
                this.resultadosProducto = [];

                this.$nextTick(() => this.enfocarBuscador());
            },

            enfocarBuscador() {
                this.$refs.buscadorProducto?.focus();
            },

            cambiarCantidad(index, cantidad) {
                const item = this.items[index];

                if (! item) {
                    return;
                }

                let nueva = Math.max(parseInt(cantidad, 10) || 1, 1);

                // Tope de stock (R4). Un stock 0 o ausente se trata como
                // desconocido para no bloquear carritos viejos de sessionStorage;
                // la autoridad final sigue siendo el 409 de VentaService.
                const tope = item.stock == null ? null : Number(item.stock);

                if (tope !== null && tope > 0 && nueva > tope) {
                    nueva = tope;
                    this.avisoStock = 'Solo quedan ' + tope + ' unidades de ' + item.nombre + '.';
                } else if (this.avisoStock) {
                    this.avisoStock = null;
                }

                item.cantidad = nueva;
                item.subtotal = (Number(item.precio) || 0) * nueva;

                this.items.splice(index, 1, item);
            },

            agregarPago() {
                this.pagos.push({ metodo: this.nuevoPago.metodo || 'Efectivo', monto: 0, referencia: '' });
            },

            pagoExacto() {
                const saldo = Number(this.saldoPendiente.toFixed(2));

                if (saldo <= 0) {
                    return;
                }

                this.pagos.push({ metodo: this.nuevoPago.metodo || 'Efectivo', monto: saldo, referencia: '' });
            },

            eliminarPago(index) {
                this.pagos.splice(index, 1);
            },

            async buscarClientes() {
                const termino = (this.busquedaCliente || '').trim();

                if (termino.length < 2) {
                    this.resultadosCliente = [];

                    return;
                }

                this.buscandoCliente = true;

                try {
                    const respuesta = await fetch(this.urlClientes + '?q=' + encodeURIComponent(termino), {
                        headers: { 'Accept': 'application/json' },
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    this.resultadosCliente = respuesta.ok ? (cuerpo.data || []) : [];
                } catch (error) {
                    this.resultadosCliente = [];
                } finally {
                    this.buscandoCliente = false;
                }
            },

            seleccionarCliente(cliente) {
                this.cliente = cliente;
                this.busquedaCliente = '';
                this.resultadosCliente = [];
            },

            limpiarCliente() {
                this.cliente = null;
            },

            abrirModalCliente() {
                this.nuevoCliente = { nombre: '', telefono: '', nit_ci: '', email: '' };
                this.errorCliente = null;
                this.modalCliente = true;
            },

            async guardarClienteRapido() {
                if (this.guardandoCliente) {
                    return;
                }

                this.guardandoCliente = true;
                this.errorCliente = null;

                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

                try {
                    const respuesta = await fetch(this.urlClienteStore, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({
                            nombre: this.nuevoCliente.nombre,
                            telefono: this.nuevoCliente.telefono || null,
                            nit_ci: this.nuevoCliente.nit_ci || null,
                            email: this.nuevoCliente.email || null,
                            activo: true,
                        }),
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (respuesta.status === 201) {
                        this.seleccionarCliente(cuerpo.data);
                        this.modalCliente = false;

                        return;
                    }

                    if (respuesta.status === 422) {
                        this.errorCliente = Object.values(cuerpo.errors || {}).flat().join(' ') || 'Revisá los datos del cliente.';

                        return;
                    }

                    if (respuesta.status === 403) {
                        this.errorCliente = 'No tenés permiso para crear clientes. Consultá con un administrador.';

                        return;
                    }

                    this.errorCliente = cuerpo.message || 'No se pudo crear el cliente.';
                } catch (error) {
                    this.errorCliente = 'No se pudo conectar con el servidor.';
                } finally {
                    this.guardandoCliente = false;
                }
            },

            get erroresCamposAutorizacion() {
                return Object.values(this.modalAutorizacion.errores || {}).flat();
            },

            abrirModalAutorizacion() {
                this.modalAutorizacion = {
                    abierto: true,
                    email: '',
                    password: '',
                    motivo: '',
                    enviando: false,
                    error: null,
                    errores: {},
                };
            },

            cerrarModalAutorizacion() {
                this.modalAutorizacion.abierto = false;
                this.modalAutorizacion.password = '';
            },

            async solicitarAutorizacion() {
                if (this.modalAutorizacion.enviando) {
                    return;
                }

                this.modalAutorizacion.enviando = true;
                this.modalAutorizacion.error = null;
                this.modalAutorizacion.errores = {};

                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

                try {
                    const respuesta = await fetch(this.urlAutorizarDescuento, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({
                            email: this.modalAutorizacion.email,
                            password: this.modalAutorizacion.password,
                            operacion_id: this.operacionId,
                            descuento_porcentaje: this.porcentajeParaEnviar,
                            monto_descuento: Number(this.descuentoMonto.toFixed(2)),
                            motivo: this.modalAutorizacion.motivo || null,
                        }),
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (respuesta.status === 201) {
                        // 5.11.5 conecta este id al payload de confirmar.
                        this.autorizacionId = cuerpo.autorizacion_id;
                        this.autorizacionAdmin = cuerpo.admin?.name || null;
                        this.modalAutorizacion.abierto = false;
                        this.modalAutorizacion.password = '';

                        return;
                    }

                    if (respuesta.status === 422) {
                        this.modalAutorizacion.errores = cuerpo.errors || {};
                        this.modalAutorizacion.error = cuerpo.message || 'Revisá los datos de la autorización.';

                        return;
                    }

                    if (respuesta.status === 429) {
                        this.modalAutorizacion.error = 'Demasiados intentos. Esperá un minuto e intentá de nuevo.';

                        return;
                    }

                    this.modalAutorizacion.error = cuerpo.message || 'No se pudo autorizar el descuento.';
                } catch (error) {
                    this.modalAutorizacion.error = 'No se pudo conectar con el servidor.';
                } finally {
                    this.modalAutorizacion.enviando = false;
                }
            },

            async abrirSeries(index) {
                const item = this.items[index];

                if (! item) {
                    return;
                }

                this.serieModal = { abierto: true, itemIndex: index, cargando: true, validando: false, series: [], error: null };

                try {
                    const url = this.urlSeries.replace('__producto__', item.producto_id);
                    const respuesta = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (! respuesta.ok) {
                        this.serieModal.error = cuerpo.message || 'No se pudieron cargar las series.';

                        return;
                    }

                    this.serieModal.series = cuerpo.data || [];
                } catch (error) {
                    this.serieModal.error = 'No se pudo conectar con el servidor.';
                } finally {
                    this.serieModal.cargando = false;
                }
            },

            // 5.7.3: la serie se valida contra el servidor antes de asignarla;
            // si ya no sirve, el error se muestra en el modal y no se asigna.
            async elegirSerie(serie) {
                const index = this.serieModal.itemIndex;
                const item = this.items[index];

                if (! item || this.serieModal.validando) {
                    return;
                }

                this.serieModal.validando = true;
                this.serieModal.error = null;

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const respuesta = await fetch(this.urlValidarSerie, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({
                            numero_serie_id: serie.id,
                            producto_id: item.producto_id,
                        }),
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (! respuesta.ok) {
                        this.serieModal.error = cuerpo.message || 'Ese número de serie ya no está disponible.';
                        this.serieModal.series = this.serieModal.series.filter((disponible) => disponible.id !== serie.id);

                        return;
                    }

                    item.numero_serie_id = serie.id;
                    item.numero_serie = cuerpo.numero_serie || serie.numero_serie;
                    this.items.splice(index, 1, item);
                    this.serieModal.abierto = false;
                } catch (error) {
                    this.serieModal.error = 'No se pudo validar el número de serie.';
                } finally {
                    this.serieModal.validando = false;
                }
            },

            quitarSerie(index) {
                const item = this.items[index];

                if (! item) {
                    return;
                }

                item.numero_serie_id = null;
                item.numero_serie = null;
                this.items.splice(index, 1, item);
            },

            limpiarBanner() {
                if (this.silenciarBanner) {
                    return;
                }

                this.ventaConfirmada = null;
            },

            limpiarAutorizacion() {
                this.autorizacionId = null;
                this.autorizacionAdmin = null;
            },

            operacionActual() {
                const guardada = sessionStorage.getItem(this.operacionKey);

                return guardada || this.nuevaOperacion();
            },

            nuevaOperacion() {
                const token = window.crypto && window.crypto.randomUUID
                    ? window.crypto.randomUUID()
                    : 'pos-' + Date.now() + '-' + Math.random().toString(16).slice(2);

                sessionStorage.setItem(this.operacionKey, token);

                return token;
            },

            async confirmarVenta() {
                if (this.enviando || this.items.length === 0 || this.pagos.length === 0) {
                    return;
                }

                this.enviando = true;
                this.errores = {};
                this.errorGeneral = null;
                this.ventaConfirmada = null;

                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

                try {
                    const respuesta = await fetch(this.urlStore, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({
                            items: this.items.map((item) => ({
                                producto_id: item.producto_id,
                                cantidad: item.cantidad,
                                numero_serie_id: item.numero_serie_id || null,
                            })),
                            pagos: this.pagos.map((pago) => ({
                                metodo: pago.metodo,
                                monto: pago.monto,
                                referencia: (pago.referencia || '').trim() || null,
                            })),
                            meta: {
                                operacion_id: this.operacionId,
                                cliente_id: this.cliente?.id ?? null,
                            },
                            descuento_porcentaje: this.porcentajeParaEnviar,
                            autorizacion_id: this.autorizacionId || null,
                        }),
                    });

                    const cuerpo = await respuesta.json().catch(() => ({}));

                    if (respuesta.status === 201) {
                        // El carrito se limpia, pero el banner debe sobrevivir:
                        // los watchers de items/pagos lo borrarían.
                        this.silenciarBanner = true;
                        this.items = [];
                        this.pagos = [];
                        this.descuento = { tipo: '%', valor: 0 };
                        this.autorizacionId = null;
                        this.autorizacionAdmin = null;
                        this.operacionId = this.nuevaOperacion();

                        await this.$nextTick();

                        this.silenciarBanner = false;
                        // Número y TOTAL del servidor: evita drift de redondeo.
                        this.ventaConfirmada = cuerpo.venta;

                        return;
                    }

                    if (respuesta.status === 422) {
                        this.errores = cuerpo.errors || {};
                        this.errorGeneral = cuerpo.errors
                            ? (cuerpo.message || 'Revisá los datos de la venta.')
                            : (cuerpo.message || 'No se pudo confirmar la venta.');

                        return;
                    }

                    if (respuesta.status === 409) {
                        this.errorGeneral = cuerpo.message || 'El producto ya no está disponible.';

                        return;
                    }

                    this.errorGeneral = cuerpo.message || 'No se pudo confirmar la venta.';
                } catch (error) {
                    this.errorGeneral = 'No se pudo conectar con el servidor. Reintentá la operación.';
                } finally {
                    this.enviando = false;
                }
            },

            cargar() {
                try {
                    const guardado = JSON.parse(sessionStorage.getItem(this.storageKey) || 'null');

                    if (guardado) {
                        // Los carritos previos a R4 no guardan stock: queda en null
                        // y el clamp no aplica (compatibilidad).
                        this.items = (Array.isArray(guardado.items) ? guardado.items : []).map((item) => ({
                            ...item,
                            stock: Number.isFinite(item.stock) ? Number(item.stock) : null,
                        }));
                        this.pagos = Array.isArray(guardado.pagos) ? guardado.pagos : [];

                        if (guardado.descuento) {
                            this.descuento = {
                                tipo: guardado.descuento.tipo === 'Bs' ? 'Bs' : '%',
                                valor: Number(guardado.descuento.valor) || 0,
                            };
                        }
                    }
                } catch (error) {
                    this.items = [];
                    this.pagos = [];
                }
            },

            persistir() {
                sessionStorage.setItem(this.storageKey, JSON.stringify({
                    items: this.items,
                    pagos: this.pagos,
                    descuento: this.descuento,
                }));
            },

            money(valor) {
                return window.formatearBs(valor);
            },
        };
    }
