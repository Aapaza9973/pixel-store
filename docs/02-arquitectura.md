# 02 — Arquitectura

Visión general del diseño técnico del proyecto.

---

## 🎯 Estilo arquitectónico

**MVC en capas con Service Layer**: los controladores delegan la lógica de negocio en servicios inyectables, y la persistencia queda encapsulada en modelos Eloquent.

**Monolito modular** — una sola aplicación Laravel con dos "super-módulos":

```
┌─────────────────────────────────────────────────────────────┐
│                        NAVEGADOR                             │
│           (Blade + Tailwind + Alpine.js + Vite)             │
└────────────────────────┬────────────────────────────────────┘
                         │ HTTP
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                    routes/web.php                            │
│       Middleware: auth · verified · user.active · can:…      │
└────────────────────────┬────────────────────────────────────┘
                         │
          ┌──────────────┴──────────────┐
          ▼                             ▼
┌──────────────────────┐      ┌──────────────────────┐
│  PANEL INTERNO       │      │  CATÁLOGO PÚBLICO     │
│  (auth + roles)      │      │  (sin registro)       │
│  Admin\ · Vendedor\  │      │  Catálogo · Carrito   │
└──────────┬───────────┘      └──────────┬───────────┘
           │                             │
           └──────────┬──────────────────┘
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                    SERVICE LAYER                             │
│  InventoryService · VentaService · CotizacionService        │
│  PedidoService · PuntosService · CajaService                │
│  PaymentService · ImportacionProductosService               │
│  CompatibilidadService · FacturacionService                 │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                     MODELOS ELOQUENT                         │
│           User · Cliente · Categoria · Marca ·               │
│           Producto · AtributoTecnico · ProductoAtributo     │
│           Ubicacion · StockUbicacion · MovimientoStock       │
│           AlertaStock · Venta · ... (20+ modelos)           │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              POSTGRESQL 18 (44 tablas + 20 ENUMs)            │
│         Índices GIN (pg_trgm) · FKs con CASCADE/RESTRICT    │
└─────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Stack tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Lenguaje | PHP | 8.3.30 |
| Framework | Laravel | 13.x |
| Plantillas | Blade | (incluido) |
| CSS | Tailwind CSS | 3.x |
| JS | Alpine.js | 3.x |
| Bundler | Vite | 7.x |
| Base de datos | PostgreSQL | 18.6 |
| Permisos | Spatie Laravel-Permission | 8.x |
| PDF | DomPDF | 3.x |
| Tests | PHPUnit | 12.x |
| Formatter | Laravel Pint | 1.x |

---

## 🗂️ Estructura de directorios

```
pixel-store/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── CheckStockAlerts.php     # Alertas automáticas
│   ├── Exceptions/
│   │   └── StockInsuficienteException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                   # CRUDs del panel interno
│   │   │   │   ├── CategoriaController.php
│   │   │   │   ├── ProductoController.php
│   │   │   │   └── UbicacionController.php
│   │   │   ├── Auth/                    # Breeze
│   │   │   ├── AlertaController.php
│   │   │   ├── Controller.php           # Base (usa AuthorizesRequests)
│   │   │   └── DashboardController.php
│   │   ├── Middleware/
│   │   │   └── CheckUserActive.php
│   │   └── Requests/
│   │       └── Admin/
│   │           ├── StoreProductoRequest.php
│   │           ├── UpdateProductoRequest.php
│   │           ├── StoreUbicacionRequest.php
│   │           ├── UpdateUbicacionRequest.php
│   │           └── TransferirStockRequest.php
│   ├── Models/                          # 20+ modelos Eloquent
│   ├── Policies/                        # Autorización por recurso
│   │   ├── AlertaStockPolicy.php
│   │   ├── ProductoPolicy.php
│   │   └── UbicacionPolicy.php
│   ├── Providers/
│   │   └── AppServiceProvider.php       # Gate::before · Policies · Composer
│   └── Services/
│       └── InventoryService.php         # Único punto de mutación de stock
├── bootstrap/
│   └── app.php                          # Registro de middleware (alias user.active)
├── database/
│   ├── migrations/
│   │   └── 2026_09_27_000001_add_estructura_fisica_to_ubicaciones_table.php
│   ├── schema/
│   │   └── pgsql-schema.sql             # Schema completo (44 tablas)
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RoleSeeder.php
│       ├── CategoriaSeeder.php
│       ├── MarcaSeeder.php
│       ├── AtributoTecnicoSeeder.php
│       ├── ValorAtributoSeeder.php
│       ├── UbicacionSeeder.php
│       └── DemoProductoSeeder.php
├── resources/
│   ├── css/app.css                      # Tailwind + tokens de diseño
│   ├── js/app.js                        # Alpine + helpers
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php            # Panel interno
│       │   └── guest.blade.php          # Login/Register
│       ├── components/                  # Blade components reutilizables
│       │   ├── nav-link.blade.php
│       │   ├── alert.blade.php
│       │   ├── card.blade.php
│       │   └── session-timeout.blade.php
│       ├── admin/
│       │   ├── categorias/
│       │   ├── productos/
│       │   └── ubicaciones/
│       ├── alertas/index.blade.php
│       ├── auth/                        # Breeze
│       ├── profile/
│       └── dashboard.blade.php
├── routes/
│   ├── web.php                          # Rutas internas + admin
│   ├── auth.php                         # Breeze
│   └── console.php                      # Scheduler
├── tests/
│   ├── Feature/                         # Feature tests
│   │   ├── AlertaTest.php
│   │   ├── DashboardTest.php
│   │   ├── InventoryServiceTest.php
│   │   ├── ProductoFilterTest.php
│   │   ├── ProductoStockTest.php
│   │   ├── ProductoTest.php
│   │   ├── RoleAccessTest.php
│   │   ├── SessionExtendTest.php
│   │   └── UbicacionTest.php
│   ├── Unit/
│   └── TestCase.php                     # Carga el schema en BD de tests
├── docs/                                # Esta documentación
├── AGENTS.md                            # Convenciones para IA
└── README.md
```

---

## 🎯 Service Layer

El corazón del proyecto está en la **capa de servicios**:

### `InventoryService` — el más crítico

**Responsabilidad**: único punto de mutación de stock en todo el sistema.

**Reglas clave**:
- Usa `DB::transaction()` en cada método
- Usa `lockForUpdate()` para evitar condiciones de carrera
- Rechaza stock negativo con `StockInsuficienteException`
- Registra cada movimiento en `movimientos_stock` con `stock_resultante`
- Genera alertas automáticas cuando `stock ≤ umbral_alerta`
- Mantiene el invariante: **`productos.stock == SUM(stock_ubicacion.cantidad)`**

**Métodos principales**:

```php
public function ajustarStock(
    Producto $producto,
    int $cantidad,           // + entrada, - salida
    string $tipo,            // entrada|salida|ajuste|venta|devolucion|baja
    ?string $motivo = null,
    ?int $ubicacionId = null,
    ?int $userId = null
): MovimientoStock;

public function transferir(
    Producto $producto,
    int $origenId,
    int $destinoId,
    int $cantidad,
    ?int $userId = null
): void;

public function hayStockSuficiente(Producto $producto, int $cantidad): bool;

public function sincronizarStockTotal(Producto $producto): void;
```

### Servicios planificados (Sprint 2+)

| Servicio | Responsabilidad |
|---|---|
| `VentaService` | Creación atómica de ventas (POS + pedidos) |
| `CotizacionService` | Ciclo de vida de cotizaciones + PDF |
| `NumerosSerieService` | Trazabilidad de unidades individuales |
| `PedidoService` | Pedidos del catálogo público |
| `PuntosService` | Fidelización |
| `CajaService` | Cierre de caja diario |
| `PaymentService` | Pagos en línea (Stripe / PayPal) |
| `FacturacionService` | Facturación electrónica SIN |
| `CompatibilidadService` | Motor de compatibilidad de componentes |

---

## 📐 Convenciones arquitectónicas

### Controladores finos
Validan entrada (`FormRequest`) y delegan a servicios. **No tocan transacciones** directamente.

### Un solo punto de escritura de stock
Todo cambio de inventario pasa por `InventoryService::ajustarStock()`. **Nunca** se hace `$producto->update(['stock' => ...])` directamente.

### Transacciones explícitas
Cada operación multi-tabla va dentro de `DB::transaction(...)`.

### Bloqueos pesimistas
`lockForUpdate()` sobre productos y saldos de puntos para evitar sobreventa.

### Autorización en 2 capas
1. **Middleware** `can:` en rutas (`Route::middleware('can:ver productos')`)
2. **Policies** en controladores (`$this->authorizeResource(Producto::class, 'producto')`)

### Jobs en cola post-commit
Las notificaciones se despachan **después** del `DB::commit`.

### Un solo punto de salida de notificaciones
Los emails y WhatsApp salen por `app/Jobs/` y `app/Notifications/`.

---

## 🎨 Frontend

### Blade components personalizados

| Componente | Uso |
|---|---|
| `<x-nav-link>` | Link del sidebar con estado activo e ícono SVG |
| `<x-alert>` | Notificación flash con auto-dismiss |
| `<x-card>` | Card con header + body + slot de acciones |
| `<x-session-timeout>` | Modal de advertencia por inactividad |

### Sistema de diseño "Taller"

| Token | Valor |
|---|---|
| Fondo oscuro | `bg-slate-950` |
| Fondo de cards | `bg-slate-900` |
| Bordes | `border-slate-800` |
| Texto principal | `text-white` |
| Texto secundario | `text-slate-400` |
| Azul primario | `bg-blue-600` |
| Rojo peligro | `text-red-400` |
| Verde éxito | `text-emerald-400` |

### Alpine.js

Usado para:
- Modales (`x-show`, `x-transition`)
- Formularios dinámicos (atributos EAV por categoría)
- Búsqueda con debounce
- Session timeout (contador regresivo)

---

## 🔐 Seguridad transversal

- **CSRF**: global con excepción firmada para webhooks
- **Rate limiting**: 5 intentos / minuto por `email + IP` en login (ver flujo abajo)
- **Cierre automático de sesión**: 30 min de inactividad
- **Middleware `user.active`**: rechaza usuarios desactivados
- **Bcrypt 12 rounds**: contraseñas
- **Email verificado**: obligatorio para el panel interno
- **Logs de auditoría**: en `logs_auditoria` (login, logout, intentos fallidos, CRUD de usuarios y cambios de rol)

---

## 🔐 Flujo de autenticación y auditoría

### Diagrama

```text
 Navegador                  Laravel                                    PostgreSQL
    │                          │                                          │
    │  GET /login              │                                          │
    ├─────────────────────────>│ AuthenticatedSessionController@create     │
    │                          │   └─ view('auth.login')                   │
    │<─────────────────────────┤                                          │
    │                          │                                          │
    │  POST /login             │                                          │
    │  email + password        │                                          │
    ├─────────────────────────>│ LoginRequest@authenticate()               │
    │                          │   1. ensureIsNotRateLimited()             │
    │                          │      (5 intentos / 60 s por email + IP)   │
    │                          │   2. Auth::attempt()                      │
    │                          │                                          │
    │                          │   ┌── éxito ──> evento Login ─────────┐   │
    │                          │   │            LogSuccessfulLogin ────┼──>│ INSERT logs_auditoria (login)
    │                          │   │                                   │   │
    │                          │   └── fallo ──> RateLimiter::hit()   │   │
    │                          │                evento Failed ────────┤   │
    │                          │                LogFailedLogin ───────┼──>│ INSERT logs_auditoria (login_fallido)
    │                          │                                      │   │
    │  302 → /dashboard        │                                      │   │
    │<─────────────────────────┤ redirect()->intended(...)             │   │
```

### Pasos

1. **Formulario** — `GET /login` → `AuthenticatedSessionController@create` → `view('auth.login')`.
2. **Validación y rate limiting** — `POST /login` → `LoginRequest` (reglas: `email` requerido/email, `password` requerido).
   - Límite **activo**: **5 intentos por minuto**, con clave `email|IP` (`RateLimiter::tooManyAttempts($key, 5)`; `hit()` usa 60 s de decaimiento).
   - Al excederlo se dispara el evento `Lockout` y se devuelve el error de validación `auth.throttle`.
3. **Auditoría de intentos fallidos** — evento `Failed` → `LogFailedLogin` → `AuditoriaService::registrarIntentoFallido()` → `logs_auditoria` (`accion = login_fallido`, `modelo = null`, `user_id = null`, `datos_nuevos = {email, ip}`).
4. **Sesión** — en éxito: `RateLimiter::clear()` + `session()->regenerate()` + evento `Login` → `LogSuccessfulLogin` → `logs_auditoria` (`accion = login`, `modelo = App\Models\User`, `modelo_id = id`).
5. **Redirección** — `redirect()->intended(route('dashboard'))`. **No existe redirección por rol**: todos los roles aterrizan en `/dashboard`; el nivel de acceso lo aplican el middleware `user.has.role` y las policies.
6. **Middleware en rutas internas**, en este orden:
   `auth` → `verified` → `user.active` → `user.has.role`
   - `user.active` (`CheckUserActive`): si `activo = false` → `Auth::logout()` + invalidación de sesión + redirect a `/login` con mensaje de error.
   - `user.has.role` (`CheckUserHasRole`): si el usuario no tiene **ningún** rol → `abort(403, 'No tiene permisos asignados.')` y registro `acceso_denegado_sin_rol`.
7. **Logout** — `POST /logout` → `AuthenticatedSessionController@destroy` → `Auth::guard('web')->logout()` + `invalidate()` + `regenerateToken()` → evento `Logout` → `LogSuccessfulLogout` → `logs_auditoria` (`accion = logout`).
8. **Observers** — `UserObserver` (registrado en `AppServiceProvider`) audita `created`, `updated` (solo el diff real) y `deleted`. Excluye `password` y `remember_token`, y si no hay usuario autenticado no registra nada.
9. **Cambios de rol** — `syncRoles()` **no** dispara eventos de Eloquent, por lo que `UserController@update` detecta el cambio y llama a `AuditoriaService::registrarCambioRoles()` (`accion = cambiar_roles_user`, con los roles antes y después).

### Eventos registrados en `logs_auditoria`

| `accion` | Origen | `modelo` |
|---|---|---|
| `login` | `LogSuccessfulLogin` | `User` |
| `login_fallido` | `LogFailedLogin` | — |
| `logout` | `LogSuccessfulLogout` | `User` |
| `crear_user` / `editar_user` / `eliminar_user` | `UserObserver` | `User` |
| `cambiar_roles_user` | `AuditoriaService::registrarCambioRoles()` | `User` |
| `acceso_denegado_sin_rol` | `CheckUserHasRole` | — |

Los listeners están registrados en `AppServiceProvider::boot()` con `Event::listen(...)` (Laravel 11 no usa `EventServiceProvider`), y todos son **best-effort**: un fallo al auditar se reporta pero nunca rompe el flujo del usuario.

### Autorización

`Gate::before()` hace **super-admin** al rol `Admin` (todos los permisos). El resto se resuelve con 4 policies registradas en `AppServiceProvider` (`ProductoPolicy`, `AlertaStockPolicy`, `UbicacionPolicy`, `UserPolicy`) sobre los permisos de Spatie. Ver [04 — Roles y permisos](04-roles-permisos.md).

---

## 🧭 Decisiones arquitectónicas clave

### 1. EAV híbrido para atributos técnicos

**Por qué**: cada categoría tiene atributos distintos (socket, RAM, vatios...). Un modelo rígido de columnas fijas no escala.

**Cómo**: tabla `atributos_tecnicos` + `valores_atributo` (enums predefinidos) + `producto_atributos` (valores concretos).

**Alternativas descartadas**:
- JSON en columna → no indexable, mala validación
- Tablas específicas por categoría → migraciones constantes

### 2. Invariante de stock

**Regla**: `productos.stock == SUM(stock_ubicacion.cantidad)` **siempre**.

**Por qué**: garantiza que el stock físico por ubicación y el agregado del producto nunca diverjan.

**Impacto**: el ajuste de stock siempre se hace sobre una ubicación concreta; nunca "en el aire".

### 3. Rechazo estricto por ubicación

Si una ubicación no tiene stock suficiente → `StockInsuficienteException`. No se "toma prestado" de otra ubicación.

**Razón operativa**: en la vida real, si hay 2 RAMs en tienda y 20 en depósito, vender 5 requiere transferir primero.

### 4. PostgreSQL sobre MySQL

**Por qué**:
- `JSONB` indexable
- Tipos `ENUM` nativos
- Índices GIN para búsqueda fuzzy (`pg_trgm`)
- Locks `lockForUpdate` más granulares
- Soporte para constraints avanzados

### 5. Blade + Alpine.js en vez de SPA

**Por qué**: menos complejidad, mejor SEO, más rápido de desarrollar, no necesita API REST.

### 6. Un solo `InventoryService`

**Por qué**: garantiza que todas las mutaciones de stock pasen por el mismo camino y respeten el invariante. Cualquier bug se detecta en un solo lugar.

---

## ➡️ Siguiente paso

- [03 — Base de datos](03-base-de-datos.md) para conocer el schema completo
- [05 — Sprint 1](05-sprint-01.md) para ver qué se ha implementado
