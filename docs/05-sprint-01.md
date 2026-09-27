# 05 — Sprint 1: Módulo Inventario

Documentación completa del primer sprint del proyecto.

---

## 📊 Resumen ejecutivo

| Concepto | Valor |
|---|---|
| **Duración** | Septiembre 2026 |
| **Estado** | ✅ **100% completado** |
| **Tests** | 96 passing (316 assertions) |
| **Formato** | PASS (Laravel Pint) |
| **HU cubiertas** | 8 historias de usuario |

---

## 🎯 Objetivos del Sprint

Implementar el **módulo de inventario completo**, incluyendo:

1. Autenticación y control de acceso por roles
2. CRUD de catálogo (categorías, productos, marcas)
3. Atributos técnicos con modelo EAV
4. Gestión de stock con trazabilidad
5. Sistema de alertas automáticas
6. Transferencias entre ubicaciones
7. Filtros y buscador avanzados

---

## 📋 Historias de usuario cubiertas

### HU 1.1 — Registro, autenticación y administración de usuarios

**Estado**: ✅ Completado

**Funcionalidades**:
- Login/logout con Breeze
- Registro de usuarios (solo Admin)
- Asignación de roles (Admin, Vendedor, Cajero, Inventario, Cliente)
- Desactivación de cuentas
- Middleware `CheckUserActive`
- Rate limiting: 5 intentos / 15 min

**Tests**: 20 passing (`AuthenticationTest`, `RegistrationTest`, `PasswordResetTest`, etc.)

### HU 4.1 y 4.2 — Inicio de sesión al módulo de inventario

**Estado**: ✅ Completado

- Login específico con redirección por rol
- Validación de credenciales
- Sesiones en BD
- Cierre automático por inactividad (30 min)

### HU 4.3 y 4.4 — Registro y gestión de productos técnicos

**Estado**: ✅ Completado

- CRUD completo de productos
- EAV dinámico: al elegir categoría, aparecen sus atributos
- Soporte de tipos: string, integer, decimal, boolean, enum
- Carga de imágenes (storage/app/public/productos/)
- SKU, código de barras, maneja_numero_serie

**Tests**: 18 passing (`ProductoTest`, `ProductoFilterTest`)

### HU 4.5 — Monitoreo en tiempo real de existencias

**Estado**: ✅ Completado

- `InventoryService` como único punto de mutación
- Actualización atómica con `lockForUpdate`
- Invariante: `productos.stock == SUM(stock_ubicacion.cantidad)`
- Registro de cada movimiento en `movimientos_stock`

**Tests**: 14 passing (`InventoryServiceTest`)

### HU 4.6 — Generación de alertas por stock mínimo

**Estado**: ✅ Completado

- Alertas automáticas cuando `stock ≤ umbral_alerta`
- Deduplicación: no repite alertas sin leer
- Bandeja de alertas en `/alertas`
- Badge con contador en el header
- Comando `inventory:check-alerts` con schedule diario 08:00

**Tests**: 8 passing (`AlertaTest`)

### HU 4.7 — Gestión de ubicación física de productos

**Estado**: ✅ Completado

- Estructura física con pasillo/estante/anaquel
- 7 subdivisiones creadas por seeder
- CRUD de ubicaciones
- Selector de ubicación en productos
- Desglose de stock por ubicación en show
- Modal de transferencia entre ubicaciones

**Tests**: 6 passing (`UbicacionTest`) + 3 en `ProductoStockTest`

### HU 4.8 — Restricción de salida por falta de stock

**Estado**: ✅ Completado

- `StockInsuficienteException` cuando no hay stock
- Validación por ubicación específica (no se "toma prestado" de otra ubicación)
- `lockForUpdate` para evitar sobreventa en concurrencia
- Mensaje claro indicando stock disponible y total en otras ubicaciones

**Tests**: Incluido en `InventoryServiceTest` y `ProductoStockTest`

---

## ✅ Entregables técnicos

### Base de datos

- **44 tablas** creadas (43 de negocio + `migrations`)
- **20 tipos ENUM** nativos de PostgreSQL
- **Extensiones**: `pg_trgm` y `unaccent` para búsqueda fuzzy
- **Seeders**: 8 seeders idempotentes

### Backend

- `InventoryService` (servicio crítico)
- `StockInsuficienteException` (excepción de dominio)
- `CheckStockAlerts` (comando artisan)
- 3 controladores admin (Producto, Categoria, Ubicacion)
- 1 controlador de alertas
- 5 FormRequests
- 3 Policies

### Frontend

- Layout `app.blade.php` con sidebar y sistema de diseño
- 4 componentes Blade: `nav-link`, `alert`, `card`, `session-timeout`
- 15+ vistas Blade (index/create/edit para cada módulo)
- Alpine.js para formularios EAV dinámicos
- Chart.js para el dashboard

### Tests

- **96 tests passing** (316 assertions)
- Cobertura por módulo:
  - Auth: 20 tests
  - Productos: 18 tests
  - InventoryService: 14 tests
  - Stock/Ubicaciones: 14 tests
  - Alertas: 8 tests
  - Ubicaciones: 6 tests
  - Dashboard: 2 tests
  - Otros: 14 tests

---

## 🎯 Decisiones técnicas clave

### 1. Modelo EAV híbrido

**Decisión**: usar EAV + valores predefinidos para atributos técnicos.

**Por qué**:
- Cada categoría tiene atributos distintos
- No requiere migración al agregar atributos nuevos
- Mantiene integridad con `valores_atributo`

**Alternativas descartadas**: JSON (no indexable), columnas específicas (migraciones constantes)

### 2. Invariante de stock

**Regla**: `productos.stock == SUM(stock_ubicacion.cantidad)`

**Por qué**: garantiza que el stock físico por ubicación y el agregado del producto nunca diverjan.

**Cómo se mantiene**: cualquier ajuste de stock pasa por `InventoryService::ajustarStock()`, que actualiza ambas tablas en una transacción.

### 3. Rechazo estricto por ubicación

**Decisión**: si una ubicación no tiene stock suficiente, se lanza `StockInsuficienteException` sin tomar prestado de otras.

**Por qué**: en la vida real, si hay 2 RAMs en tienda y 20 en depósito, vender 5 requiere transferir primero. El sistema refleja la realidad operativa.

### 4. PostgreSQL sobre MySQL

**Por qué**:
- JSONB indexable
- Tipos ENUM nativos
- Índices GIN (pg_trgm)
- Locks lockForUpdate más granulares
- Mejor rendimiento en concurrencia

### 5. Blade + Alpine en vez de SPA

**Por qué**:
- Menos complejidad
- Mejor SEO
- No necesita API REST
- Desarrollo más rápido

---

## 🐛 Bugs resueltos (aprendizajes)

### Bug 1 — Route parameter mal singularizado

**Problema**: `Route::resource('ubicaciones')` generaba `{ubicacione}` (Laravel singulariza con reglas inglesas).

**Impacto**: el route model binding no inyectaba `$ubicacion` → PUT/DELETE operaban sobre modelo vacío.

**Solución**:

```php
Route::resource('ubicaciones', UbicacionController::class)
    ->parameters(['ubicaciones' => 'ubicacion']);
```

### Bug 2 — CSRF roto por `session()->regenerate()`

**Problema**: `regenerate()` rota el ID de sesión y regenera el CSRF token, pero el response no devolvía el token nuevo.

**Impacto**: el segundo "Extender sesión" y el logout fallaban con 419.

**Solución**: usar `save()` en lugar de `regenerate()` y devolver `csrf_token()` en el JSON.

### Bug 3 — `beforeunload` rompía bfcache

**Problema**: `destroy()` en `beforeunload` quitaba timers; al volver con botón atrás, el timer ya no funcionaba.

**Solución**: usar `pagehide`/`pageshow` con `event.persisted`.

### Bug 4 — `nav-link` duplicaba enlaces

**Problema**: `nav-link.blade.php` tenía un bloque Breeze residual que renderizaba cada link del sidebar dos veces.

**Solución**: eliminar el bloque Breeze y quedarse solo con el componente Pixel Store.

### Bug 5 — `write_file` inicial perdido

**Problema**: una llamada de escritura no se aplicó (quedó versión antigua).

**Solución**: detectado por tests fallidos, reescrito con verificación.

---

## 📊 Métricas finales

| Métrica | Valor |
|---|:-:|
| Tablas | 44 |
| Tipos ENUM | 20 |
| Modelos Eloquent | 20+ |
| Servicios | 1 (+ 9 planificados) |
| Controladores | 5 |
| Policies | 3 |
| Componentes Blade | 4 personalizados |
| Vistas Blade | 15+ |
| Tests | 96 |
| Assertions | 316 |
| Archivos trackeados en Git | ~200 |

---

## 🎯 Lecciones aprendidas

### 1. Tests como red de seguridad

Los tests detectaron bugs sutiles (route binding, CSRF) que hubieran llegado a producción. **Testear en cada paso** ahorra tiempo.

### 2. Un solo punto de escritura

Tener `InventoryService` como único punto de mutación de stock simplifica la lógica y evita bugs de estado inconsistente.

### 3. Invariantes explícitos

Definir el invariante `productos.stock == SUM(stock_ubicacion.cantidad)` y hacer que los tests lo verifiquen garantiza integridad.

### 4. Español como idioma de dominio

Usar `Producto`, `Venta`, `Cotizacion` (no `Product`, `Sale`) hace el código más legible para el equipo local.

### 5. PostgreSQL vale la pena

Aunque requiere setup inicial (instalar, configurar extensión, PATH), los beneficios a largo plazo (JSONB, ENUMs, índices GIN) valen.

---

## ➡️ Siguiente sprint

**Sprint 2 — POS, Cotizaciones y Números de serie**

Objetivos:
- Punto de venta con validación de stock en tiempo real
- Cotizaciones con PDF y validez configurable
- Trazabilidad de números de serie
- Pago mixto y descuentos por rol
- Devoluciones con reversión de puntos
- Cierre de caja diario

Ver [09 — Roadmap](09-roadmap.md) para el plan completo.

---

## ➡️ Enlaces

- [02 — Arquitectura](02-arquitectura.md)
- [03 — Base de datos](03-base-de-datos.md)
- [04 — Roles y permisos](04-roles-permisos.md)
- [07 — Testing](07-testing.md)
