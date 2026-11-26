# 18 — Plan de cierre del Sprint 3 (T41–T49)

**Fecha**: 2026-11-26
**Base**: `Doc requerimientos/Backlog Sprint 3.xlsx` (HU-S3-01…09, T01–T40)
**Cronograma**: `Doc requerimientos/Cronograma Sprint 3.xlsx` (G9–G16)
**Estado**: planificación cerrada · **sin cambios de código**

---

## 1. Por qué existe este documento

La auditoría del backlog del 2026-11-26 encontró nueve huecos entre lo que las
historias prometen y lo que las tareas T01–T40 cubren. Este documento los
convierte en tareas verificables (T41–T49), define el cronograma que faltaba y
fija el **cut-off de G9**.

No se agregaron historias ni puntos: las 9 HU y sus 55 puntos siguen igual. Se
agregaron **30 horas** de trabajo de cierre (78 → **108 h**), repartidas en
**9 tareas** (40 → **49**).

| ID | Cierra el hueco | Capa | Horas | Dueño | Día | Depende de | Gate |
|---|---|---|---:|---|---|---|---|
| T41 | Cronograma y gates G9–G16 + cut-off de G9 | Ops | 2 | Einar | D1 | — | G9–G16 |
| T42 | Subida de comprobante por token (ruta, FormRequest, disco privado, vista, test) | HTTP + Vista + Test | 5 | Alvaro | D3 | T06 | G11 |
| T43 | Disparadores de correo + rechazo + recordatorio único + destinatario interno | Servicio + DB | 5 | Brandon | D4–D5 | T10, T11, T07 | G12 |
| T44 | Webhook: CSRF, cuerpo crudo, firma, `pago_eventos` único y tests | HTTP + DB + Test | 3 | Jose | D2–D3 | T02, T04 | G10 |
| T45 | Tarifa de envío configurable (fija/zonas/gratis) y su administración | DB + Servicio + HTTP + Vista | 4 | Alvaro | D2–D3 | T18 | G14 |
| T46 | CRUD de cupones + convivencia con `DescuentoService` | DB + HTTP + Vista + Test | 4 | Brandon | D3–D4 | T33 | G14 |
| T47 | Unificar `Pago` en `pagarPedidoWeb` y fecha contable del cierre | Servicio + Test | 3 | Alvaro | D4 | T07, T20 | G14 |
| T48 | Alinear permisos/policy de pedidos + test 403 aprobar/rechazar | Policy + Test | 2 | Jose | D4 | T08 | G11 |
| T49 | Extender `docs/08` (cola, scheduler, monitoreo, SSL, backups) + decisión fiscal/legal | Ops | 2 | Einar | D5–D6 | T38, T39 | G16 |

**Total agregado**: 9 tareas · 30 h · 108 h acumuladas.

---

## 2. Tareas

### T41 — Cronograma Sprint 3 y gates G9–G16

**Objetivo**: que cada gate tenga criterio, día, responsable y dependencia, y que
G9 tenga un cut-off explícito.

**Archivos**:
- `Doc requerimientos/Cronograma Sprint 3.xlsx` (nuevo)
- `docs/18-plan-sprint-3.md` (este documento)

**Criterios de aceptación**:
- [ ] La hoja de gates define G9–G16 con criterio verificable, día, responsable y dependencia.
- [ ] G9 tiene cut-off: **fin de D1**. Si no hay proveedor habilitado para Bolivia, se activa el plan B (HU-S3-02) y HU-S3-01 no se codifica.
- [ ] La hoja día por día cruza D1–D7 con tareas, gates y responsables.
- [ ] La carga suma 49 tareas / 108 h y coincide con el backlog.

**Nota**: el Cronograma Sprint 2 solo define G0–G8. Este archivo es el que faltaba;
no se modifica el cronograma anterior.

---

### T42 — Subida de comprobante por token

**Objetivo**: cumplir el primer criterio de HU-S3-02 (“el comprador puede subir
una imagen o PDF del comprobante desde el estado del pedido”), que T06–T08 no
cubren.

**Archivos**:
- `routes/web.php` (GET/POST `/pedido/{venta}/comprobante/{token}`)
- `app/Http/Controllers/ComprobantePagoController.php` (nuevo)
- `app/Http/Requests/ComprobantePagoRequest.php` (nuevo)
- `app/Models/ComprobantePago.php` (creado en T06)
- `resources/views/catalogo/comprobante-pago.blade.php` (nuevo)
- `config/filesystems.php` (disco `comprobantes`, local y privado)
- `tests/Feature/ComprobantePagoTest.php` (nuevo)

**Criterios**:
- [ ] La ruta valida el token de 48 caracteres con `hash_equals`; sin token válido responde 404.
- [ ] Acepta JPG/PNG/PDF hasta 5 MB; rechaza el resto con mensaje en español.
- [ ] Guarda en disco privado (nunca en `public/`); el panel lo sirve con URL firmada o descarga autenticada.
- [ ] Un pedido `Pagado`/`Cancelada` no admite una subida nueva.
- [ ] Test: subida válida, archivo inválido, token inválido, pedido no pendiente.

**Decisión**: el comprobante pertenece a la venta, no al cliente; se guarda con
`venta_id`, `path`, `estado`, `aprobado_por`, `motivo_rechazo`, `created_at`.

---

### T43 — Disparadores de correo y posventa

**Objetivo**: los cuatro correos de HU-S3-03 existen como clases (T10) pero no
se envían desde ningún flujo. Además falta el rechazo de HU-S3-02 y la marca
para no repetir el recordatorio.

**Archivos**:
- `app/Mail/PagoRechazado.php` (nuevo)
- `app/Http/Controllers/CheckoutController.php` (aviso interno de pedido nuevo)
- `app/Services/ConciliacionService.php` (aprobado/rechazado)
- `app/Http/Controllers/PedidoPublicoController.php` y `Admin/PedidoWebController.php` (cancelado)
- `app/Console/Commands/ExpirarPedidosWeb.php` (cancelado por vencimiento)
- `database/migrations/*_add_recordatorio_enviado_en_to_ventas.php` (nuevo)
- `config/mail.php` (destinatario interno configurable)
- `tests/Feature/PedidoNotificacionesTest.php` (T13, ampliado)

**Criterios**:
- [ ] Se envían 5 correos: pago aprobado, pago rechazado, pedido cancelado, pedido por vencer y aviso interno.
- [ ] El aviso interno sale al destinatario configurado, no a un email hardcodeado.
- [ ] El recordatorio sale 6 h antes del vencimiento y `recordatorio_enviado_en` evita duplicados.
- [ ] La cancelación por panel, por el comprador y por `pedidos:expirar` dispara el mismo correo.
- [ ] Un fallo de correo se reporta y no revierte la transición de la venta.
- [ ] Tests con `Mail::fake()` por cada disparador y por el “no se repite”.

**Nota**: `ExpirarPedidosWeb` ya existe y corre cada hora; no se duplica el comando,
se engancha la notificación.

---

### T44 — Endurecer el webhook de cobro

**Objetivo**: que un POST del proveedor pueda entrar y que la idempotencia sea
real, no solo una intención en la tarea T04.

**Archivos**:
- `bootstrap/app.php` (`validateCsrfTokens(except: ['webhooks/*'])`)
- `app/Http/Controllers/CobroController.php`
- `app/Models/PagoEvento.php`
- `database/migrations/*_create_pago_eventos_table.php` (creado en T02)
- `tests/Feature/CobroWebhookTest.php` (nuevo)

**Criterios**:
- [ ] La ruta del webhook no pasa por CSRF y lee el cuerpo crudo (`getContent()`), no el payload parseado.
- [ ] Verifica la firma del proveedor; firma inválida → 401 y no toca datos.
- [ ] `pago_eventos` tiene único `(proveedor, evento_id)`; un evento repetido devuelve 200 sin duplicar pago ni ingreso.
- [ ] Evento válido con monto distinto al pedido no marca Pagado: queda para revisión.
- [ ] Pago que llega después de la expiración no revive el pedido; se registra para conciliación manual.
- [ ] Tests: firma inválida, evento duplicado, evento válido, pago tardío.

---

### T45 — Tarifa de envío configurable

**Objetivo**: HU-S3-05 pide mostrar el costo antes de confirmar, pero no existe
de dónde sacarlo.

**Archivos**:
- `database/migrations/*_create_tarifas_envio_table.php`
- `app/Models/TarifaEnvio.php`
- `app/Services/EnvioService.php`
- `app/Http/Controllers/Admin/TarifaEnvioController.php`
- `app/Http/Requests/Admin/StoreTarifaEnvioRequest.php` / `UpdateTarifaEnvioRequest.php`
- `app/Policies/TarifaEnvioPolicy.php`
- `resources/views/admin/tarifas-envio/{index,create,edit,show}.blade.php`
- `database/seeders/TarifaEnvioSeeder.php`
- `tests/Feature/TarifaEnvioTest.php`

**Modelo propuesto**: `nombre`, `tipo` (`fija`/`zona`/`gratis_desde`), `monto`,
`monto_minimo`, `activo`. Para `zona`, `monto` es el costo y `monto_minimo` el
umbral de envío gratis; la zona se resuelve por la dirección de entrega.

**Criterios**:
- [ ] Retiro en tienda siempre cuesta 0.
- [ ] Delivery toma la tarifa activa; sin tarifa configurada, el checkout bloquea el delivery con mensaje claro.
- [ ] `gratis_desde` exige `monto_minimo` y lo valida el FormRequest.
- [ ] El CRUD sigue el patrón `crud-module` (policy, FormRequests, 7 rutas, sidebar, tests).
- [ ] Test: fija, gratis desde monto, sin tarifa, 403 sin permiso.

**Decisión pendiente del dueño**: valores iniciales de la tarifa y si habrá zonas
(La Paz / El Alto / provincias) o una sola tarifa plana.

---

### T46 — CRUD de cupones y convivencia con descuentos

**Objetivo**: que las campañas de HU-S3-08 se puedan crear sin tipear en la base
y que el cupón no se pise con el descuento por rol.

**Archivos**:
- `app/Http/Controllers/Admin/CuponController.php`
- `app/Http/Requests/Admin/StoreCuponRequest.php` / `UpdateCuponRequest.php`
- `app/Policies/CuponPolicy.php`
- `app/Models/Cupon.php` (T33)
- `app/Services/CuponService.php` (T33)
- `resources/views/admin/cupones/{index,create,edit,show}.blade.php`
- `tests/Feature/CuponAdminTest.php`

**Modelo propuesto**: `codigo` único, `tipo` (`porcentaje`/`monto`), `valor`,
`monto_minimo`, `usos_maximos`, `usos`, `vigente_desde`, `vigente_hasta`,
`activo`.

**Regla de convivencia**:
- El descuento por rol sigue en `DescuentoService` y `descuento_porcentaje`.
- El cupón se aplica **después** del descuento por rol y se guarda en
  `ventas.cupon_id` + `ventas.descuento_cupon` (migración de T35).
- Un cupón nunca autoriza superar el límite de descuento del rol; si el total
  supera el límite, se rechaza con mensaje claro.
- El uso se incrementa dentro de la transacción de la venta y solo si la venta
  queda confirmada.

**Criterios**:
- [ ] CRUD completo con 7 rutas, policy, sidebar, paginación y tests (mínimo 5).
- [ ] Código único, vigencia y usos validados en el FormRequest.
- [ ] Test: cupón + descuento por rol no duplica el beneficio; cupón agotado no se consume.

---

### T47 — Unificar `Pago` en `pagarPedidoWeb` y fecha contable

**Objetivo**: cerrar la herencia más peligrosa. Hoy `pagarPedidoWeb()` crea
`Ingreso` pero **no** `Pago`; el cierre de caja suma `pagos` y un pedido cobrado
desde el panel no aparece. Solo el controlador de simulación crea el `Pago` a mano.

**Archivos**:
- `app/Services/VentaService.php` (`pagarPedidoWeb`)
- `app/Http/Controllers/PagoPedidoController.php` (quitar el `Pago::firstOrCreate` manual)
- `app/Services/CierreCajaService.php` (fecha contable)
- `tests/Feature/CierreCajaWebTest.php` (nuevo)

**Criterios**:
- [ ] `pagarPedidoWeb()` crea `Pago` con `monto = total`, método correcto (`Transferencia`, `QR`, `Efectivo`) y referencia del proveedor si existe.
- [ ] Un pedido cobrado desde el panel aparece en el cierre del día con su método.
- [ ] No se puede crear un segundo `Pago` para la misma venta por doble clic.
- [ ] El cierre usa la **fecha del pago** (`pagos.created_at`) para caja, no la fecha de creación del pedido; los reportes de ventas siguen usando `ventas.fecha`.
- [ ] Test: pedido creado ayer y cobrado hoy cae en el cierre de hoy; total coincide con pedido, comprobante y correo.

---

### T48 — Permisos y policy de pedidos

**Objetivo**: alinear los permisos que ya existen (`ver pedidos`, `confirmar pedidos`,
`cancelar pedidos`) con las rutas, que hoy usan `ver ventas`, `editar ventas` y
`cancelar ventas`. El Vendedor tiene `confirmar pedidos` pero no `editar ventas`,
así que hoy no puede aprobar.

**Archivos**:
- `database/seeders/RoleSeeder.php`
- `app/Policies/VentaPolicy.php` o `PedidoWebPolicy.php` (según el recurso)
- `app/Http/Controllers/Admin/PedidoWebController.php`
- `resources/views/admin/pedidos-web/show.blade.php` (`@can`)
- `tests/Feature/PedidoWebPermisosTest.php` (nuevo)

**Criterios**:
- [ ] Ver bandeja = `ver pedidos`; aprobar/rechazar = `confirmar pedidos`; cancelar = `cancelar pedidos`.
- [ ] Admin conserva todo; Vendedor puede confirmar y cancelar si el dueño lo autoriza; Cajero e Inventario reciben 403.
- [ ] Los botones del panel se ocultan con `@can` y las rutas también lo verifican.
- [ ] Test: 200/403 por rol, aprobar y rechazar, cancelar.

**Decisión pendiente**: si el Vendedor debe poder cancelar pedidos web o solo el
Admin. Se documenta en la matriz de `docs/04`.

---

### T49 — Runbook de salida y decisión fiscal/legal

**Objetivo**: que la salida a producción no dependa de una checklist incompleta
y que la facturación simulada sea una decisión explícita, no un olvido.

**Archivos**:
- `docs/08-despliegue.md` (extender, no duplicar)
- `docs/17-alcance-externo.md` (decisión fiscal/legal)
- `Doc requerimientos/Checklist salida Sprint 3.md`

**Criterios**:
- [ ] `docs/08` cubre cola (`queue:work` + supervisor), scheduler, SSL/renovación, backups y restore probado, monitoreo (Sentry/Uptime), logrotate, firewall y `APP_DEBUG=false`.
- [ ] La checklist de salida referencia `docs/08`, no crea un runbook paralelo.
- [ ] `PAGOS_MODO` es coherente con G9: `produccion` si hay pasarela; `simulado`/deshabilitado si se usa transferencia con comprobante.
- [ ] Hay un acta firmada que decide si se sale con facturación SIN simulada o se posterga la salida hasta tener proveedor fiscal.
- [ ] Se documentan términos, privacidad y política de devoluciones del checkout.

---

## 3. Cronograma G9–G16

| Gate | Hito | Día | Criterio de aceptación | Responsable | Depende de | Cut-off / plan B |
|---|---|---|---|---|---|---|
| **G9** | Proveedor de cobro decidido | D1 | Documento con proveedor habilitado para Bolivia, comisión, plazo y comprobante; o acta que activa el plan B | Jose | — | **Fin de D1**. Sin decisión: plan B (HU-S3-02) y HU-S3-01 no se codifica |
| G10 | Cobro y webhook idempotente | D2 | Cobro creado y consultado en sandbox; webhook con firma, evento duplicado sin doble pago; T44 en verde | Jose | G9 | Si G9 fue plan B, G10 se reemplaza por el criterio de conciliación |
| G11 | Conciliación de comprobante | D4 | Subida por token, aprobar/rechazar con auditoría, doble aprobación rechazada, 403, correo de rechazo | Jose | G9 | — |
| G12 | Correos de posventa | D5 | 5 correos disparados, recordatorio único 6 h antes, fallo de correo no rompe el flujo | Brandon | G11 | — |
| G13 | Recuperación del pedido | D4 | `/mi-pedido` con token, detalle, cancelación con reingreso, 404/throttle | Alvaro | G11 | — |
| G14 | Conversión: envío + cupones + caja | D5 | Tarifa aplicada, total cuadra en pedido/correo/comprobante/cierre; cupón válido/agotado; T45–T47 en verde | Alvaro / Brandon | G13 | — |
| G15 | SEO y medición | D5 | og:image, BreadcrumbList válido, sitemap con categorías, Search Console, purchase único, utm persistidos | Franco | G14 | — |
| G16 | Salida a producción | D7 | Checklist `docs/08`, secretos rotados, SMTP real, `PAGOS_MODO` coherente, compra real E2E, decisión fiscal/legal y acta | Einar | G12, G14, G15 | Sin acta no se publica; se posterga la salida, no se recorta |

**Regla de G9**: el cut-off es deliberadamente duro. Si al cierre de D1 no hay
proveedor con cuenta habilitada, comisión y comprobante documentados, el sprint
cambia a “transferencia + comprobante aprobado” y HU-S3-01 queda fuera. No se
improvisa una integración con Stripe, que no abre cuentas de comercio en Bolivia.

---

## 4. Carga actualizada

| Dev | Tareas | Horas | h/día (7 días) | Historias |
|---|---:|---:|---:|---|
| Alvaro | 13 | 28 | 4,0 | HU-S3-02, HU-S3-04, HU-S3-05 |
| Jose | 11 | 33 | 4,7 | HU-S3-01, HU-S3-02 |
| Brandon | 10 | 23 | 3,3 | HU-S3-03, HU-S3-08 |
| Einar | 6 | 12 | 1,7 | HU-S3-09 |
| Franco | 9 | 12 | 1,7 | HU-S3-06, HU-S3-07 |
| **Total** | **49** | **108** | **3,1** | 9 HU |

Capacidad: 5 devs × 7 días × 8 h = **280 h**; uso **39 %**. Jose sigue en el
camino crítico (cobro + conciliación + webhook + permisos).

---

## 5. Decisiones que deben cerrarse antes de D1

1. **G9**: proveedor de cobro o plan B. Sin esto no arranca T03/T04.
2. **Tarifas de envío**: valores y zonas (T45).
3. **Destinatario interno** del aviso de pedido nuevo (T43).
4. **Proveedor de analítica** y consentimiento de cookies (HU-S3-07).
5. **Facturación fiscal**: salir con SIN simulado o postergar la salida (T49).
6. **Permiso de cancelación** para el Vendedor (T48).

---

## 6. Riesgos que el plan deja visibles

- **Pago tardío**: si el webhook llega después de `pedidos:expirar`, el pedido ya
  está cancelado. T44 lo deja para conciliación manual; no se revive solo.
- **Fecha contable**: hoy el cierre usa `ventas.fecha`; un pedido creado ayer y
  cobrado hoy cae en el día equivocado. T47 lo corrige con `pagos.created_at`.
- **Dirección de entrega**: `crearPedidoOnline` guarda la dirección en `clientes`,
  no en la venta. Si el mismo cliente compra con otra dirección, se pisa la
  anterior. T45 debe decidir si se agrega snapshot en `ventas`.
- **Cupón + descuento por rol**: sin la regla de T46, se pueden acumular dos
  beneficios y superar el límite autorizado.
- **Facturación**: una venta web real sin SIN válido es un riesgo fiscal; queda
  como decisión de G16, no como detalle de implementación.

---

## 7. Fuera de alcance (sin cambios)

- WhatsApp saliente (requiere Business + plantillas).
- SIN real y PDF fiscal (bloqueados por proveedor).
- Multi-sucursal y API pública (Fase 6).
- Burndown histórico: no hay horas reales registradas (`docs/17`).

---

## 8. Trazabilidad

- Backlog: `Doc requerimientos/Backlog Sprint 3.xlsx`, hojas 2 (T41–T49), 3 y 4.
- Cronograma: `Doc requerimientos/Cronograma Sprint 3.xlsx`, hojas 1 (gates),
  2 (día por día), 3 (carga).
- Deuda y alcance: `TECH_DEBT.md`, `docs/13-sprint-3.md`, `docs/17-alcance-externo.md`.
- Reglas de implementación: `skills/hu-development.md`, `skills/crud-module.md`,
  `skills/frontend-design.md`, `skills/frontend-storefront.md`.
