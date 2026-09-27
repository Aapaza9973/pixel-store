# 09 — Roadmap

Plan de desarrollo por fases del proyecto Pixel Store.

---

## 📊 Visión general

| Fase | Nombre | Duración estimada | Estado |
|:-:|---|---|:-:|
| 1 | Fundación | 4 semanas | ✅ **100%** |
| 2 | Trazabilidad y cotizaciones | 4 semanas | 🚧 En desarrollo |
| 3 | Catálogo público y pedidos | 4 semanas | ⏳ Pendiente |
| 4 | Facturación y proveedores | 4 semanas | ⏳ Pendiente |
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

## 🚧 Fase 2 — Trazabilidad y cotizaciones (En desarrollo)

### Objetivos
- Cotizaciones formales con PDF y validez configurable
- Trazabilidad completa de números de serie
- POS completo con pago mixto y descuentos por rol
- Devoluciones con reversión de puntos
- Cierre de caja diario
- Reportes básicos

### Tareas planificadas

#### 2.1 — Cotizaciones
- [ ] `CotizacionService`
- [ ] `CotizacionController`
- [ ] Generación de PDF con DomPDF
- [ ] Validez configurable (default 4 días)
- [ ] Estados: Vigente, Vencida, Convertida, Anulada
- [ ] Envío por WhatsApp/correo
- [ ] Conversión de cotización a venta
- [ ] Tests: 8-10

#### 2.2 — Números de serie
- [ ] `NumeroSerieService`
- [ ] CRUD de números de serie
- [ ] Vinculación automática al vender
- [ ] Consulta por número de serie (garantías)
- [ ] Estados: disponible, vendido, devuelto, defectuoso, baja
- [ ] Tests: 10-12

#### 2.3 — POS (Punto de Venta)
- [ ] `VentaService`
- [ ] Interfaz de POS con búsqueda rápida
- [ ] Validación de stock en tiempo real
- [ ] Pago mixto (efectivo + tarjeta + transferencia)
- [ ] Descuentos por rol (5% vendedor, 10% admin)
- [ ] Registro de número de serie obligatorio para CPUs/GPUs
- [ ] Comprobante imprimible (80mm y A4)
- [ ] Tests: 12-15

#### 2.4 — Devoluciones
- [ ] `DevolucionService`
- [ ] Solicitud y aprobación
- [ ] Reposición de stock
- [ ] Reversión proporcional de puntos
- [ ] Tests: 6-8

#### 2.5 — Cierre de caja
- [ ] `CajaService`
- [ ] Resumen del día por método de pago
- [ ] Cierre único por vendedor/día
- [ ] Exportación PDF
- [ ] Tests: 4-6

#### 2.6 — Reportes básicos
- [ ] Reporte de ventas diarias
- [ ] Productos más vendidos
- [ ] Ventas por vendedor
- [ ] Exportación CSV

### Duración estimada: 4 semanas

---

## ⏳ Fase 3 — Catálogo público y pedidos

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

## ⏳ Fase 4 — Facturación y proveedores

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

| Fase | Tests | Servicios | Modelos |
|:-:|:-:|:-:|:-:|
| 1 ✅ | 96 | 1 | 20+ |
| 2 🚧 | ~130 (esperado) | 3 | +8 |
| 3 ⏳ | ~150 | 5 | +4 |
| 4 ⏳ | ~170 | 7 | +4 |
| 5 ⏳ | ~180 | 8 | +2 |
| 6 ⏳ | ~200 | 10 | +4 |

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
