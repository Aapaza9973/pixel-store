# Deuda Técnica — Pixel Store

Registro de deuda técnica identificada durante el desarrollo, con su
prioridad y sprint objetivo.

---

## 🔴 Prioridad alta

### Auth — Bloquear login de usuario inactivo de raíz
- [x] Resuelto: `LoginRequest@authenticate` incluye `'activo' => true` en
      `Auth::attempt`, así que una cuenta desactivada no obtiene sesión.
- Un usuario con `activo=false` **podía autenticarse** (`POST /login` → 302 al
  panel); el bloqueo ocurría después, en `CheckUserActive`.
- **Impacto (histórico)**: el usuario despedido obtenía una sesión válida hasta
  que un middleware lo expulsara en la siguiente request.
- **Detectado**: Wave 2 Bloque 3 (commit `a8bdbad`)
- **Cerrado**: bloque de seguridad post-Wave 4 (`tests/Feature/Auth/AuthenticationTest`
  cubre el caso). El mensaje sigue siendo genérico a propósito: revelar
  "cuenta desactivada" en el login permitiría enumerar usuarios.

### Sprint 2 — Modelos pendientes
- [x] Crear modelo `Venta` (tabla `ventas` ya existe en schema)
- [x] Crear modelo `CierreCaja` (tabla `cierres_cajas` ya existe en schema)
- [x] Descomentar relaciones `ventas()` y `cierresCaja()` en `app/Models/User.php`
- **Cerrado**: los modelos ya existen y `User::ventas()` quedó descomentada
  (`cierresCaja()` ya estaba activa).

---

## 🟡 Prioridad media

### Bug — Ruta `/alertas` duplicada en `routes/web.php`
- [x] Eliminar la declaración duplicada de `alertas.index`.
- **Cerrado**: eliminada la duplicada fuera del grupo `user.has.role`; la ruta
  queda protegida por el middleware correcto. La autorización real la sigue
  resolviendo `AlertaStockPolicy` vía `authorizeResource`.
- **Detectado**: Wave 2 Bloque 2 (commit `bc621e1`)

### Refactor pendiente — `app/Models/MovimientoStock.php`
No sigue el patrón profesional de las skills:
- [ ] Agregar `declare(strict_types=1)`
- [ ] Agregar PHPDoc con `@property`
- [ ] Agregar `HasFactory` trait
- [ ] Agregar scopes (`buscar`, por tipo, por rango de fechas)
- [ ] Agregar método `estaDisponible()` o similar

**Cuándo arreglarlo**: cuando se toque el módulo de movimientos (Sprint 2).

### Refactor — Servicios de inventario consolidados
- [x] Unificar `InventarioService` (métricas de solo lectura) dentro de
      `InventoryService` (mutación de stock).
- **Cerrado**: `resumen()` se movió a `InventoryService`; `InventarioService.php`
  fue eliminado; `InventarioController` y `DashboardController` ahora inyectan
  `InventoryService`.
- **Motivo**: dos clases para el mismo dominio (español vs inglés) violaban la
  regla "no mezclar idiomas". El usuario eligió `InventoryService` como canónico.

### Tailwind — dependencia `@tailwindcss/vite` (v4) colgada + plan de migración
- [ ] Decidir y ejecutar una de las dos rutas (ver plan abajo).
- **Situación**: `package.json` declara `@tailwindcss/vite@^4.3.3` **y**
  `tailwindcss@^3.1.0`. El build real usa **Tailwind v3** vía `postcss.config.js`
  + `tailwind.config.js` (config v3 con `theme.extend`); `vite.config.js` **no**
  importa `@tailwindcss/vite`, así que el plugin v4 está **instalado pero inactivo**.
- **Conclusión**: el sistema Obsidian Cyber Grid ya está implementado como
  `theme.extend` de v3 (colores, tipografía, borderRadius en `tailwind.config.js`),
  por lo que **no requiere migrar a v4**.
- **Plan (recomendado: opción A)**:
  - **A. Quedarse en v3**: quitar `@tailwindcss/vite` de `devDependencies`
    (`npm uninstall @tailwindcss/vite`) para eliminar la ambigüedad. Cero riesgo.
  - **B. Migrar a v4** (solo si se necesita el engine nuevo): reemplazar
    `tailwindcss` + `@tailwindcss/vite`, conectar el plugin en `vite.config.js`,
    migrar `tailwind.config.js` → CSS-first `@theme`, y verificar las clases
    legacy (`slate-*`, `blue-*`) del panel admin no migrado. Esfuerzo alto.
- **Cuándo arreglarlo**: antes de tocar estilos del panel admin o del catálogo.

### Bug — Tabla `telescope_entries` no migrada
- [x] Publicar migración + `php artisan migrate`.
- **Cerrado**: se publicó con
  `php artisan vendor:publish --tag=telescope-migrations` y se ejecutó
  `php artisan migrate`, creando `telescope_entries` (y tablas relacionadas).
- **Detectado**: durante verificación manual del Bloque 3

### Bug — Vista `historial.blade.php` faltante
- [x] Crear `resources/views/admin/usuarios/historial.blade.php`
- Ruta `GET admin/usuarios/{user}/historial` devuelve 500 porque
  `UserController@historial()` hace `view('admin.usuarios.historial')`
  pero la vista no existe.
- **Detectado**: durante tarea 1.1.8 (commit 2e45c07)
- **Cerrado**: Bloque 3 (vista creada + link en `show` + `UserHistorialTest`)

### Código muerto — UserController
- [x] Eliminar alias `historialAccesos()` (sin ruta, sin vista, sin test)
- [x] Eliminar claves duplicadas `'accesos'` y `'logs'` en `show()`
      (la vista solo usa `$ultimosAccesos`)
- **Detectado**: durante auditoría Bloque 0
- **Cerrado**: Bloque 3 (commit `fix(usuarios): crear vista historial`)

### Auditoría — homogeneizar try/catch best-effort
- [ ] Verificar que los 3 métodos de `AuditoriaService`
      (`registrarLoginExitoso`, `registrarLogout`, `registrarIntentoFallido`)
      no propaguen excepciones al usuario. Si sus callers (listeners)
      no los protegen, agregar `try/catch + report()` internos.
- [ ] Verificar lo mismo en `CheckUserHasRole` middleware
- **Detectado**: durante tarea 2.B (discrepancia entre la asunción del
  prompt y el código real)
- **Cuándo arreglarlo**: Sprint 2 al refactorizar auditoría

### Config muerta — `RateLimiter::for('login')` sin aplicar
- [x] Eliminar el limiter de `AppServiceProvider` (ninguna ruta aplicaba
      `throttle:login`)
- Definido como `Limit::perMinute(15, 5)` (15 intentos / 5 min) pero **ninguna
  ruta lo usaba**. El límite activo real es de `LoginRequest` (5 intentos / 60 s,
  por `email + IP`).
- **Detectado**: durante tarea 1.1.18 (documentación del flujo de auth)
- **Cerrado**: `2ecdc43` — el limiter ya no existe en el código.

---

## 🟢 Prioridad baja

### UX — Usuario sin rol puede ver `/dashboard`
- [ ] Un usuario autenticado sin rol puede acceder a `/dashboard` (solo está
      protegido por `auth` + `verified` + `user.active`); `/admin/*` sí exige
      `user.has.role`.
- **Impacto**: bajo (sin rol no puede operar nada), pero el dashboard muestra
      una UI vacía sin contexto.
- **Fix sugerido**: mover `/dashboard` detrás de `user.has.role`, o mostrar un
      empty-state "sin permisos asignados".
- **Detectado**: Wave 2 Bloque 3 (commit `a8bdbad`)
- **Cuándo arreglarlo**: cuando se defina el flujo completo de usuario sin rol.

### Middleware en ruta de movimientos
- [ ] Agregar `->middleware('can:editar productos')` a la ruta
      `admin.movimientos.salida` en `routes/web.php`

**Actual**: cualquier usuario autenticado puede invocarla si conoce la URL.
**Riesgo**: bajo (no hay UI, requiere conocer la URL exacta, el service valida stock).

### Assets — Falta logo para fondos oscuros
- [ ] Encargar/generar `pixel-logo-white.png` (isotipo + wordmark blanco)
- El único logo completo (`pixel-logo-horizontal.png`) tiene píxeles oscuros
  → bajo contraste sobre `slate-950`
- Tratamiento actual: icono + wordmark manual en Space Grotesk
- **Detectado**: spike /preview (validación frontend-design)
- **Cuándo arreglarlo**: antes de producción del catálogo público

---

## Convención

- Cada ítem debe tener: **descripción clara** + **cuándo arreglarlo**
- Se revisa al inicio de cada sprint
- Los ítems completados se mueven a `CHANGELOG.md` con el commit que los cerró


