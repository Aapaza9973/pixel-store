# 09 — Roadmap

Plan de desarrollo por fases del proyecto Pixel Store.

---

## 📊 Visión general

| Fase | Nombre | Duración estimada | Estado |
|:-:|---|---|:-:|
| 1 | Fundación | 4 semanas | ✅ **100%** |
| 2 | Trazabilidad y cotizaciones | 4 semanas | ✅ **100%** (cerrado 2026-11-05) |
| 3 | Catálogo público y pedidos | 4 semanas | 🚧 Parcial (catálogo, checkout, pedidos web, encuesta) |
| 4 | Facturación y proveedores | 4 semanas | ✅ **100%** (facturación en modo simulación) |
| 5 | Motor de compatibilidad e IA | 4 semanas | ⏳ Pendiente |
| 6 | Escalabilidad y multi-sucursal | Futuro | ⏳ Pendiente |

---

## ✅ Fase 1 — Fundación (Completada)

### Objetivos
- Autenticación y roles
- CRUD de productos, categorías, marcas, atributos
- Inventario con ubicaciones
- POS básico
- Alertas de stock
- Dashboard con métricas

### Entregables
- ✅ 44 tablas en PostgreSQL
- ✅ 5 roles + 60 permisos
- ✅ CRUD completo de productos con EAV
- ✅ `InventoryService` (crítico)
- ✅ Sistema de alertas
- ✅ Transferencias entre ubicaciones
- ✅ Filtros y buscador
- ✅ Dashboard con Chart.js
- ✅ 96 tests passing

---

## ✅ Fase 2 — Trazabilidad y cotizaciones (completada)

### Objetivos
- Cotizaciones formales con PDF y validez configurable
- Trazabilidad completa de números de serie
- POS completo con pago mixto y descuentos por rol
- Devoluciones con reversión de puntos
- Cierre de caja diario
- Reportes básicos

### Estado real (Sprint 2 cerrado 2026-11-05)

Implementado y con tests:
- POS completo (`VentaService`: pago mixto, descuentos por rol, autorización de admin, comprobante)
- Devoluciones (`DevolucionService`: reingreso de stock, liberación de serie, estados parcial/total)
- Cierre de caja (`CierreCajaService`: cierre único por vendedor/día, reporte diario)
- Reportes (`ReporteService` + `ReporteController`: período, vendedor, categoría, métodos, indicadores, PDF y Excel)
- Cotizaciones (`CotizacionService` + PDF + conversión a venta)
- Clientes (CRUD, historial y puntos)
- Números de serie (`NumeroSerieService`, vinculación en venta y liberación en devolución)

Cerrado el 2026-11-16:
- Cotizaciones enviables por correo y compartibles por WhatsApp (`admin.cotizaciones.enviar`)
- Devoluciones con solicitud, aprobación, rechazo y reversión proporcional de puntos
- Número de serie obligatorio vía `productos.maneja_numero_serie` en el POS

### Tareas planificadas

#### 2.1 — Cotizaciones ✅
- [x] `CotizacionService`
- [x] `CotizacionController`
- [x] Generación de PDF con DomPDF
- [x] Validez configurable (default 4 días)
- [x] Estados: Vigente, Vencida, Convertida, Anulada
- [x] Envío por WhatsApp/correo (`CotizacionEnviada` + enlace `wa.me`)
- [x] Conversión de cotización a venta
- [x] Tests: 8-10

#### 2.2 — Números de serie (parcial)
- [x] `NumeroSerieService`
- [x] CRUD de números de serie (`admin/series`, 7 rutas + policy + tests)
- [x] Vinculación automática al vender
- [x] Consulta por número de serie (garantías) en el listado y el detalle
- [x] Estados: disponible, vendido, devuelto, defectuoso, baja
- [x] Tests: 10-12

#### 2.3 — POS (Punto de Venta) ✅
- [x] `VentaService`
- [x] Interfaz de POS con búsqueda rápida
- [x] Validación de stock en tiempo real
- [x] Pago mixto (efectivo + tarjeta + transferencia)
- [x] Descuentos por rol (10% vendedor, 5% cajero, 100% admin) + autorización
- [x] Número de serie obligatorio (por producto con `maneja_numero_serie`, validado en `VentaService`)
- [x] Comprobante imprimible (80mm y A4)
- [x] Tests: 12-15

#### 2.4 — Devoluciones ✅
- [x] `DevolucionService`
- [x] Solicitud y aprobación (`devoluciones.index`, estados pendiente/procesada/rechazada)
- [x] Reposición de stock
- [x] Reversión proporcional de puntos (1 punto por Bs 10, configurable)
- [x] Tests: 6-8

#### 2.5 — Cierre de caja ✅
- [x] `CierreCajaService` (nombre final del servicio)
- [x] Resumen del día por método de pago
- [x] Cierre único por vendedor/día
- [x] Exportación PDF del cierre (`admin.caja.pdf`)
- [x] Tests: 4-6

#### 2.6 — Reportes básicos ✅
- [x] Reporte de ventas diarias
- [x] Productos más vendidos
- [x] Ventas por vendedor
- [x] Exportación CSV

### Duración estimada: 4 semanas

---

## 🚧 Fase 3 — Catálogo público y pedidos (parcial)

### Objetivos
- Catálogo público sin autenticación
- Carrito y checkout sin cuenta
- Pedidos online con confirmación por correo
- Encuestas anónimas
- Notificaciones WhatsApp/correo

### Estado real (2026-11-16)

Implementado y con tests:
- Catálogo público (`/catalogo`, ficha `/producto/{slug}`, filtros, favoritos)
- Carrito en localStorage + checkout sin cuenta (`catalogo.checkout.*`)
- Pedido web como venta `Pendiente` con reserva de stock
- Token de 48 caracteres para confirmar/cancelar desde el correo (`pedido.confirmar`, `pedido.cancelar`)
- Correo de confirmación (`App\Mail\PedidoConfirmacion`)
- Bandeja interna `admin/pedidos-web` con cobro y cancelación
- Encuesta anónima de 3 preguntas + panel `admin/encuestas`

Pendiente:
- Pagos en línea (Stripe/PayPal) y webhook firmado: requiere proveedor y claves
- Filtros por atributos EAV y búsqueda `pg_trgm`
- Notificaciones por WhatsApp

### Tareas planificadas

#### 3.1 — Catálogo público
- [x] Rutas `/catalogo`
- [x] Filtros avanzados (categoría, marca, precio, disponibilidad)
- [ ] Filtros por atributos técnicos (EAV)
- [x] Ficha de producto con especificaciones
- [x] Productos similares
- [ ] Búsqueda con `pg_trgm`

#### 3.2 — Carrito y checkout
- [x] Carrito (localStorage, no sesión)
- [x] Checkout sin cuenta
- [x] Validación de stock
- [x] Confirmación de pedido

#### 3.3 — Pedidos online
- [x] `PedidoService` (cubierto por `VentaService`: alta, cobro y cancelación)
- [x] Token de 48 chars para confirmar/cancelar
- [x] Correo de confirmación
- [x] Bandeja de pedidos interna
- [x] Conversión de pedido a venta (el pedido ya es una venta `Pendiente`)

#### 3.4 — Pagos en línea (simulación lista; pasarelas reales pendientes)
- [ ] Stripe Checkout
- [ ] PayPal Orders
- [ ] Webhook firmado
- [x] Modo simulación sin claves (`config/pagos.php`, `pedido.pagar`)

#### 3.5 — Encuesta anónima
- [x] Formulario de 3 preguntas (`/encuesta`, sin IP ni usuario)
- [x] Resultados internos (`admin/encuestas`)

### Duración estimada: 4 semanas

---

## 🚧 Fase 4 — Facturación y proveedores (proveedores y OC ✅)

### Objetivos
- Facturación electrónica SIN (CUCU API / Libélula)
- Gestión de proveedores y órdenes de compra
- Reportes avanzados
- Auditoría completa

### Tareas planificadas

#### 4.1 — Facturación electrónica (simulación completa)
- [x] `FacturacionService`
- [ ] Integración con CUCU API / Libélula (requiere credenciales del proveedor)
- [x] Generación de CUF + XML (simulados; la firma digital real depende del proveedor)
- [x] XML descargable desde el detalle (PDF fiscal queda con el proveedor)
- [x] Anulación de facturas con motivo
- [x] Registro en `facturas_electronicas`

#### 4.2 — Proveedores
- [ ] CRUD de proveedores
- [ ] Órdenes de compra formales con PDF
- [ ] Recepción de mercancía
- [ ] Actualización de stock y costos

#### 4.3 — Reportes avanzados
- [x] Utilidad neta por producto (dentro de Indicadores)
- [x] Antigüedad de inventario (dentro de Indicadores)
- [x] Comparativa de meses (`admin/reportes/comparativa`)
- [x] Exportación PDF (indicadores) y Excel de caja

#### 4.4 — Auditoría
- [x] `logs_auditoria` con quién, cuándo, qué
- [x] Consulta por usuario, acción, fecha (`admin/auditoria`)
- [x] Exportación CSV con los filtros aplicados

### Duración estimada: 4 semanas

---

## ⏳ Fase 5 — Motor de compatibilidad e IA

### Objetivos
- Motor de compatibilidad basado en reglas
- Sugerencias de alternativas
- Advertencias de cuellos de botella

### Tareas planificadas

#### 5.1 — Motor de reglas
- [ ] `CompatibilidadService`
- [ ] `compatibilidad_reglas` con reglas editables
- [ ] Validaciones: socket, tipo_ram, vatios, longitud GPU

#### 5.2 — Interfaz
- [ ] Selector de componentes
- [ ] Validación en tiempo real
- [ ] Sugerencias de alternativas

#### 5.3 — IA (futuro)
- [ ] Modelo de recomendación con scikit-learn
- [ ] Predicción de cuellos de botella
- [ ] API interna en Laravel

### Duración estimada: 4 semanas

---

## ⏳ Fase 6 — Escalabilidad y multi-sucursal

### Objetivos
- Soporte multi-sucursal
- API pública para integraciones
- E-commerce formal con pasarelas adicionales (QR, cripto)
- Módulo de servicios técnicos

### Tareas planificadas

#### 6.1 — Multi-sucursal
- [ ] Tabla `sucursales`
- [ ] Stock por sucursal
- [ ] Reportes consolidados
- [ ] Transferencias entre sucursales

#### 6.2 — API pública
- [ ] API REST con Sanctum
- [ ] Documentación OpenAPI
- [ ] Rate limiting por API key

#### 6.3 — E-commerce formal
- [ ] Pasarelas adicionales (QR, cripto)
- [ ] Multi-moneda
- [ ] Integración con delivery

#### 6.4 — Servicios técnicos
- [ ] Módulo de armado de PC
- [ ] Instalación de software
- [ ] Mantenimiento preventivo

### Duración estimada: Futuro

---

## 🎯 Hitos clave del negocio

| Hito | Fase | Impacto |
|---|:-:|---|
| Digitalización completa del inventario | 1 | ✅ Completado |
| Trazabilidad de garantías | 2 | Valor inmediato para clientes |
| Catálogo online | 3 | Nuevo canal de ventas |
| Facturación SIN integrada | 4 | Cumplimiento legal |
| Diferenciación con motor de compatibilidad | 5 | Ventaja competitiva |
| Multi-sucursal | 6 | Escalabilidad del negocio |

---

## 📊 Métricas de progreso

Snapshot real (2026-11-16):

| Métrica | Valor |
|---|---|
| Tests | 428 passing (1.797 assertions) |
| Servicios | 13 |
| Modelos | 37 |
| Policies | 9 |
| Controladores Admin | 21 |
| FormRequests | 20 |
| Rutas | 205 |
| Tablas | 48 |

---

## 🤔 Decisiones estratégicas

### ¿Por qué PostgreSQL?
Mejor rendimiento, JSONB indexable, ENUMs nativos, índices GIN para búsqueda fuzzy.

### ¿Por qué Blade y no SPA?
Menos complejidad, mejor SEO, no necesita API REST separada.

### ¿Por qué EAV?
Cada categoría tiene atributos técnicos distintos; EAV no requiere migración.

### ¿Por qué InventoryService?
Único punto de mutación garantiza el invariante `productos.stock == SUM(stock_ubicacion.cantidad)`.

---

## 🎯 Priorización

**Criterios**:
1. **Impacto inmediato en el negocio** (ventas, cumplimiento)
2. **Dependencias técnicas** (POS necesita números de serie)
3. **Complejidad** (más simple primero)
4. **Requerimientos legales** (SIN)

**Orden de ejecución**: 2 → 3 → 4 → 5 → 6

---

## ➡️ Siguiente paso

Ver los sprints completados:
- [05 — Sprint 1](05-sprint-01.md) ✅

Ver el código actual:
- [Repositorio GitHub](https://github.com/Aapaza9973/pixel-store)

---

## 🏁 Cierre de Sprint 2

**Sprint 2 cerrado el 2026-11-05.** 355 tests passing (354 de módulos + 1 E2E de cierre), 92 del gate y 12 de `VentaPosTest`. Detalle de aceptación, validación manual y decisiones D2/D3 en [10 — QA final Sprint 2](10-qa-final-sprint2.md).

- [Sprint 2 — retro](12-demo-retro-sprint2.md): guion de demo de 5 minutos y retrospectiva del cierre (369 tests finales, 0 regresiones, 10 commits de cierre).
