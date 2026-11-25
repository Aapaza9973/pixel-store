# 13 — Corte de catálogo público (el «Sprint 3» que nombraba este doc)

**Fecha**: 2026-11-16 · **Revisado**: 2026-11-25
**Estado**: ✅ cerrado en lo implementado · ⏳ el cobro real va en el Sprint 3 del cronograma

> **Nota de numeración (2026-11-25)**: este corte (7–16 nov) se llamó «Sprint 3»
> en la documentación vieja. El **Sprint 3** del cronograma es el de cobro real,
> posventa y salida a producción, y vive en `Doc requerimientos/Backlog Sprint 3.xlsx`
> (HU-S3-01…09, T01–T40). Este documento es la **línea base** de ese sprint, no su plan.

---

## ✅ Entregado en este sprint

| Bloque | Alcance | Evidencia |
|---|---|---|
| Catálogo público | `/catalogo`, ficha `/producto/{slug}`, filtros, favoritos | `CatalogoIndexTest`, `CatalogoTest`, `FavoritoTest` |
| Carrito + checkout | Carrito en `localStorage`, checkout sin cuenta, reserva de stock | `CheckoutTest`, `CatalogoStockOcultoTest` |
| Pedidos web | Bandeja `admin/pedidos-web`, cobro y cancelación con reingreso | `PedidoWebTest` |
| Confirmación por correo | Token de 48 caracteres, rutas `pedido.confirmar` / `pedido.cancelar`, `PedidoConfirmacion` | `PedidoConfirmacionTest` (4) |
| Números de serie | CRUD `admin/series` + consulta de garantía | `NumeroSerieModuleTest` (6) |
| Cierre de caja | Comprobante PDF por turno | `CierreCajaPdfTest` (2) |

---

## 🔐 Decisiones tomadas

### Token de 48 caracteres en lugar de URL firmada para el correo

El correo se reenvía y se abre en otros dispositivos: una URL firmada con
caducidad dejaría de funcionar. El token vive en `ventas.token_confirmacion`
(`char(48)`, único) y se compara con `hash_equals`. `venta_id` es secuencial,
así que el token es la única credencial: sin él, la ruta devuelve 404.

### La encuesta anónima se retiró del sistema (2026-11-25)

El corte la había entregado (`EncuestaTest`, 5 tests), pero no producía datos
accionables y sumaba una tabla de texto libre. Se eliminaron modelo,
controladores, vistas, ruta, permiso y test; la migración
`2026_11_16_000007_drop_encuestas_table` borra la tabla y sus asignaciones, con
un `down()` que la recrea. Ni `docs/09` ni este documento la listan como
entregada.

### El pedido web ya es una venta

No se creó `PedidoService`: `VentaService::crearPedidoOnline()` crea la venta
`Pendiente` con `origen = web`, reserva stock y devuelve el registro. Duplicar
la entidad habría obligado a sincronizar dos fuentes de verdad.

---

## ⏳ Pendiente

Solo queda lo que depende de terceros o del dueño del producto:

1. **Cobro con pasarela**: ver HU-S3-01/HU-S3-02 del `Backlog Sprint 3.xlsx`.
   Stripe no habilita cuentas de comercio en Bolivia; el plan B es transferencia
   con comprobante aprobado en el panel.
2. **Notificaciones salientes por WhatsApp**: requiere cuenta Business y
   plantillas aprobadas; hoy el aviso del pedido sale por correo.
3. ~~**Logo blanco** para fondos oscuros~~: generado el 2026-11-25 en
   `public/images/logo/pixel-logo-white.png`.

Cerrado después de este corte (2026-11-25):

- **Filtros por atributos EAV** en `/catalogo` (`atributos[id]=valor`) y
  **búsqueda difusa** con `pg_trgm` + `unaccent`: `CatalogoFiltrosTest`.
- **Devoluciones** con solicitud/aprobación y reversión de puntos (`docs/15`).
- **Cotizaciones por WhatsApp/correo** (`docs/15`).
- **Número de serie**: la bandera quedó por producto (`maneja_numero_serie`),
  más preciso que por categoría; `VentaService` lanza `DomainException`.
- **Encuesta anónima**: retirada (migración `drop_encuestas_table`).

---

## 🔀 Nota de integración

La rama remota tiene un commit (`b52d895`) con una implementación paralela del
módulo Shop (`app/Contracts`, `app/DTOs`, `app/Services/Cart`,
`app/Http/Controllers/Shop/*`). Convive con el catálogo ya existente
(`CatalogoController` / `CheckoutController`) y toca `AppServiceProvider`,
`phpunit.xml` y `composer.lock`, así que **no se rebasó a ciegas**.

**Decisión (2026-11-25)**: se queda la implementación ya integrada
(`CatalogoController` / `CheckoutController` / `VentaService::crearPedidoOnline`),
que es la que tiene tests y está en `main`. La rama `respaldo-shop` se conserva
como respaldo y **no se rebasa**; sus clases no existen en el working tree, así
que no hay dos módulos compitiendo.
