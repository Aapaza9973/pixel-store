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

### Refactor — `app/Models/MovimientoStock.php`
- [x] `declare(strict_types=1)`
- [x] PHPDoc con `@property`
- [x] `HasFactory`
- [x] Scopes (`porTipo`, `enRango`)
- [x] Helpers `esEntrada()` / `esSalida()`

**Cerrado (2026-11-16)**: el modelo ya cumple el patrón; no quedaba nada por hacer.

### Refactor — Servicios de inventario consolidados
- [x] Unificar `InventarioService` (métricas de solo lectura) dentro de
      `InventoryService` (mutación de stock).
- **Cerrado**: `resumen()` se movió a `InventoryService`; `InventarioService.php`
  fue eliminado; `InventarioController` y `DashboardController` ahora inyectan
  `InventoryService`.
- **Motivo**: dos clases para el mismo dominio (español vs inglés) violaban la
  regla "no mezclar idiomas". El usuario eligió `InventoryService` como canónico.

### Tailwind — dependencia `@tailwindcss/vite` (v4) colgada + plan de migración
- [x] Decidir y ejecutar una de las dos rutas (ver plan abajo).
- **Cerrado (2026-11-16)**: `package.json` ya no declara `@tailwindcss/vite`; el build usa Tailwind v3 vía PostCSS. No hay migración pendiente.
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
- [x] Verificar que los 3 métodos de `AuditoriaService`
      (`registrarLoginExitoso`, `registrarLogout`, `registrarIntentoFallido`)
      no propaguen excepciones al usuario. Si sus callers (listeners)
      no los protegen, agregar `try/catch + report()` internos.
- [x] Verificar lo mismo en `CheckUserHasRole` middleware
- **Cerrado (2026-11-16)**: los tres listeners (`LogSuccessfulLogin`,
      `LogSuccessfulLogout`, `LogFailedLogin`) y `CheckUserHasRole` envuelven la
      escritura en `try/catch` + `report()`; un fallo de auditoría nunca corta
      el login ni el request.
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

### Facturación y pagos en modo simulación
- [ ] Integrar CUCU / Libélula (SIN) y la pasarela que defina **G9** (QR Simple,
      transferencia con comprobante o merchant of record). Stripe no habilita
      cuentas de comercio en Bolivia.
- **Situación**: `FacturacionService` y `PagoPedidoController` funcionan en modo
  simulación (`config/pagos.php`), con el esquema definitivo y tests.
- **Riesgo**: una factura emitida en simulación **no tiene validez tributaria**;
  la pantalla lo advierte y el XML lleva `<modo>simulacion</modo>`.
- **Cuándo arreglarlo**: cuando el negocio contrate y entregue las claves.

## 🟢 Prioridad baja

### UX — Usuario sin rol puede ver `/dashboard`
- [x] Resuelto como **decisión de producto**, no como fix: el acceso está
      cubierto por `LoginRedirectTest::test_usuario_sin_rol_accede_al_dashboard_pero_no_al_admin`
      (200 en `/dashboard`, 403 en `/admin/*`).
- **Impacto**: bajo (sin rol no puede operar nada).
- **Nota**: si se decide cerrar `/dashboard`, hay que cambiar ese test y el
      redirect de `panelHome()` a la vez.
- **Detectado**: Wave 2 Bloque 3 (commit `a8bdbad`)

### Middleware en ruta de movimientos
- [x] Agregar `->middleware('can:editar productos')` a la ruta
      `admin.movimientos.salida` en `routes/web.php`
- **Cerrado (2026-11-16)**: la ruta ya tiene el middleware y vive dentro del
      grupo `admin` + `user.has.role`.

**Riesgo residual**: bajo y ya mitigado; la ruta exige `can:editar productos`
desde el cierre.

### Assets — Logo para fondos oscuros
- [x] Generar `pixel-logo-white.png` (isotipo + wordmark blanco)
- **Cerrado (2026-11-25)**: `public/images/logo/pixel-logo-white.png`
  (2218 × 892, fondo transparente) generado desde el logo horizontal con
  `.tools/logo_blanco.php`; el emblema azul se conserva y el wordmark pasa a
  blanco respetando el antialias. Verificado sobre `#0a0e14`.
- El único logo completo (`pixel-logo-horizontal.png`) tiene píxeles oscuros
  → bajo contraste sobre fondos oscuros
- **Detectado**: spike /preview (validación frontend-design)
- **Cuándo arreglarlo**: antes de producción del catálogo público

---

## Convención

- Cada ítem debe tener: **descripción clara** + **cuándo arreglarlo**
- Se revisa al inicio de cada sprint
- Los ítems completados se marcan `[x]` acá con su fecha o commit; el repo
  todavía no tiene `CHANGELOG.md`


