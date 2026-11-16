# 13 — Sprint 3: catálogo, pedidos y encuesta

**Fecha**: 2026-11-16
**Estado**: ✅ cerrado en lo implementado · ⏳ pagos en línea pendientes
**Suite**: 407 tests passing (1.742 assertions) · 191 rutas

---

## ✅ Entregado en este sprint

| Bloque | Alcance | Evidencia |
|---|---|---|
| Catálogo público | `/catalogo`, ficha `/producto/{slug}`, filtros, favoritos | `CatalogoIndexTest`, `CatalogoTest`, `FavoritoTest` |
| Carrito + checkout | Carrito en `localStorage`, checkout sin cuenta, reserva de stock | `CheckoutTest`, `CatalogoStockOcultoTest` |
| Pedidos web | Bandeja `admin/pedidos-web`, cobro y cancelación con reingreso | `PedidoWebTest` |
| Confirmación por correo | Token de 48 caracteres, rutas `pedido.confirmar` / `pedido.cancelar`, `PedidoConfirmacion` | `PedidoConfirmacionTest` (4) |
| Encuesta anónima | `/encuesta` (3 preguntas, sin IP ni usuario) y `admin/encuestas` | `EncuestaTest` (5) |
| Números de serie | CRUD `admin/series` + consulta de garantía | `NumeroSerieModuleTest` (6) |
| Cierre de caja | Comprobante PDF por turno | `CierreCajaPdfTest` (2) |

---

## 🔐 Decisiones tomadas

### Token de 48 caracteres en lugar de URL firmada para el correo

El correo se reenvía y se abre en otros dispositivos: una URL firmada con
caducidad dejaría de funcionar. El token vive en `ventas.token_confirmacion`
(`char(48)`, único) y se compara con `hash_equals`. `venta_id` es secuencial,
así que el token es la única credencial: sin él, la ruta devuelve 404.

### La encuesta no guarda IP ni usuario

`encuestas` solo tiene las tres notas, el comentario y `venta_id` opcional. No
hay forma de vincular una respuesta a una persona, que es lo que promete la
pantalla.

### El pedido web ya es una venta

No se creó `PedidoService`: `VentaService::crearPedidoOnline()` crea la venta
`Pendiente` con `origen = web`, reserva stock y devuelve el registro. Duplicar
la entidad habría obligado a sincronizar dos fuentes de verdad.

---

## ⏳ Pendiente

1. **Pagos en línea** (3.4): Stripe Checkout / PayPal Orders + webhook firmado.
   Requiere elegir proveedor, instalar SDK y manejar claves; no se implementó
   para no inventar credenciales ni un flujo de pago falso.
2. **Filtros por atributos EAV** y búsqueda `pg_trgm`.
3. **Devoluciones**: flujo de solicitud/aprobación y reversión de puntos.
4. **Cotizaciones por WhatsApp/correo**: hoy el PDF se descarga y se comparte.
5. **Número de serie obligatorio por categoría**: el POS valida la serie si se
   envía, pero no la exige; falta definir la bandera por categoría.
6. **Logo blanco** para fondos oscuros (`pixel-logo-white.png`).

---

## 🔀 Nota de integración

La rama remota tiene un commit (`b52d895`) con una implementación paralela del
módulo Shop (`app/Contracts`, `app/DTOs`, `app/Services/Cart`,
`app/Http/Controllers/Shop/*`). Convive con el catálogo ya existente
(`CatalogoController` / `CheckoutController`) y toca `AppServiceProvider`,
`phpunit.xml` y `composer.lock`, así que **no se rebasó a ciegas**: hay que
decidir cuál de las dos implementaciones queda antes de integrar.
