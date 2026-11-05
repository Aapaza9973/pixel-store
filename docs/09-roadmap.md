# 09 — Roadmap

Plan de desarrollo por fases del proyecto Pixel Store.

---

## 📊 Visión general

| Fase | Nombre | Duración estimada | Estado |
|:-:|---|---|:-:|
| 1 | Fundación | 4 semanas | ✅ **100%** |
| 2 | Trazabilidad y cotizaciones | 4 semanas | ✅ **100%** (cerrado 2026-11-05) |
| 3 | Catálogo público y pedidos | 4 semanas | 🚧 Parcial (ficha + favoritos) |
| 4 | Facturación y proveedores | 4 semanas | 🚧 Parcial (proveedores/OC ✅) |
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

Pendiente:
- CRUD completo de números de serie y consulta de garantías (hoy cubierto a nivel de servicio)
- Envío automático de cotizaciones por WhatsApp/correo (se comparte el PDF)

### Tareas planificadas

#### 2.1 — Cotizaciones ✅
- [x] `CotizacionService`
- [x] `CotizacionController`
- [x] Generación de PDF con DomPDF
- [x] Validez configurable (default 4 días)
- [x] Estados: Vigente, Vencida, Convertida, Anulada
- [ ] Envío por WhatsApp/correo (se descarga/comparte el PDF)
- [x] Conversión de cotización a venta
- [x] Tests: 8-10

#### 2.2 — Números de serie (parcial)
- [x] `NumeroSerieService`
- [ ] CRUD de números de serie (sin controller ni vistas propias)
- [x] Vinculación automática al vender
- [ ] Consulta por número de serie (garantías)
- [x] Estados: disponible, vendido, devuelto, defectuoso, baja
- [x] Tests: 10-12

#### 2.3 — POS (Punto de Venta) ✅
- [x] `VentaService`
- [x] Interfaz de POS con búsqueda rápida
- [x] Validación de stock en tiempo real
- [x] Pago mixto (efectivo + tarjeta + transferencia)
- [x] Descuentos por rol (10% vendedor, 5% cajero, 100% admin) + autorización
- [ ] Registro de número de serie obligatorio por categoría (hoy se valida si se envía)
- [x] Comprobante imprimible (80mm y A4)
- [x] Tests: 12-15

#### 2.4 — Devoluciones ✅
- [x] `DevolucionService`
- [ ] Solicitud y aprobación (la devolución se registra procesada)
- [x] Reposición de stock
- [ ] Reversión proporcional de puntos
- [x] Tests: 6-8

#### 2.5 — Cierre de caja ✅
- [x] `CierreCajaService` (nombre final del servicio)
- [x] Resumen del día por método de pago
- [x] Cierre único por vendedor/día
- [ ] Exportación PDF del cierre
- [x] Tests: 4-6

#### 2.6 — Reportes básicos ✅
- [x] Reporte de ventas diarias
- [x] Productos más vendidos
- [x] Ventas por vendedor
- [x] Exportación CSV

### Duración estimada: 4 semanas

---

## 🚧 Fase 3 — Catálogo público y pedidos (parcial: ficha + favoritos)

### Objetivos
- Catálogo público sin autenticación
- Carrito y checkout sin cuenta
- Pedidos online con confirmación por correo
- Encuestas anónimas
- Notificaciones WhatsApp/correo

### Tareas planificadas

#### 3.1 — Catálogo público
- [ ] Rutas `/catalogo`
- [ ] Filtros avanzados (categoría, marca, precio, disponibilidad)
- [ ] Filtros por atributos técnicos (EAV)
- [ ] Ficha de producto con especificaciones
- [ ] Productos similares
- [ ] Búsqueda con `pg_trgm`

#### 3.2 — Carrito y checkout
- [ ] Carrito en sesión
- [ ] Checkout sin cuenta
- [ ] Validación de stock
- [ ] Confirmación de pedido

#### 3.3 — Pedidos online
- [ ] `PedidoService`
- [ ] Token de 48 chars para confirmar/cancelar
- [ ] Correo de confirmación
- [ ] Bandeja de pedidos interna
- [ ] Conversión de pedido a venta

#### 3.4 — Pagos en línea
- [ ] Stripe Checkout
- [ ] PayPal Orders
- [ ] Webhook firmado
- [ ] Modo simulación sin claves

#### 3.5 — Encuesta anónima
- [ ] Formulario de 3 preguntas
- [ ] Resultados internos

### Duración estimada: 4 semanas

---

## 🚧 Fase 4 — Facturación y proveedores (proveedores y OC ✅)

### Objetivos
- Facturación electrónica SIN (CUCU API / Libélula)
- Gestión de proveedores y órdenes de compra
- Reportes avanzados
- Auditoría completa

### Tareas planificadas

#### 4.1 — Facturación electrónica
- [ ] `FacturacionService`
- [ ] Integración con CUCU API / Libélula
- [ ] Generación de CUF + firma digital
- [ ] PDF/XML
- [ ] Anulación de facturas
- [ ] Registro en `facturas_electronicas`

#### 4.2 — Proveedores
- [ ] CRUD de proveedores
- [ ] Órdenes de compra formales con PDF
- [ ] Recepción de mercancía
- [ ] Actualización de stock y costos

#### 4.3 — Reportes avanzados
- [ ] Utilidad neta por producto
- [ ] Antigüedad de inventario
- [ ] Comparativa de meses
- [ ] Exportación PDF/Excel

#### 4.4 — Auditoría
- [ ] `logs_auditoria` con quién, cuándo, qué
- [ ] Consulta por usuario, acción, fecha
- [ ] Exportación CSV

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

Snapshot real (2026-11-05):

| Métrica | Valor |
|---|---|
| Tests | 355 passing (1.535 assertions) |
| Servicios | 12 |
| Modelos | 34 |
| Policies | 8 |
| Controladores Admin | 16 |
| FormRequests | 18 |
| Tablas | 47 |

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
