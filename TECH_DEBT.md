# Deuda Técnica — Pixel Store

Registro de deuda técnica identificada durante el desarrollo, con su
prioridad y sprint objetivo.

---

## 🔴 Prioridad alta

### Sprint 2 — Modelos pendientes
- [ ] Crear modelo `Venta` (tabla `ventas` ya existe en schema)
- [ ] Crear modelo `CierreCaja` (tabla `cierres_cajas` ya existe en schema)
- [ ] Descomentar relaciones `ventas()` y `cierresCaja()` en `app/Models/User.php`
- [ ] Bloquea: HU del POS (Sprint 2), Cierre de Caja (Sprint 2)

---

## 🟡 Prioridad media

### Refactor pendiente — `app/Models/MovimientoStock.php`
No sigue el patrón profesional de las skills:
- [ ] Agregar `declare(strict_types=1)`
- [ ] Agregar PHPDoc con `@property`
- [ ] Agregar `HasFactory` trait
- [ ] Agregar scopes (`buscar`, por tipo, por rango de fechas)
- [ ] Agregar método `estaDisponible()` o similar

**Cuándo arreglarlo**: cuando se toque el módulo de movimientos (Sprint 2).

### Bug — Tabla `telescope_entries` no migrada
- [ ] Ejecutar `php artisan telescope:install` + `php artisan migrate` en local
      (o desinstalar Telescope si no se usa activamente)
- 25.449 errores `Undefined table: telescope_entries` acumulados en
  `storage/logs/laravel.log` desde el 2026-09-23
- **Impacto**: ensucia el log real; un bug crítico puede quedar enterrado
- **Detectado**: durante verificación manual del Bloque 3
- **Cuándo arreglarlo**: antes de empezar el Sprint 2

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
- [ ] Decidir: aplicar `throttle:login` a `POST /login` en `routes/auth.php`
      **O** eliminar el limiter de `AppServiceProvider`
- Definido como `Limit::perMinute(15, 5)` (15 intentos / 5 min) pero **ninguna
  ruta lo usa**. El límite activo real es de `LoginRequest` (5 intentos / 60 s,
  por `email + IP`).
- **Riesgo**: un dev puede asumir que el límite es 15/5 min cuando no es así.
- **Detectado**: durante tarea 1.1.18 (documentación del flujo de auth)
- **Cuándo arreglarlo**: al refactorizar seguridad en Sprint 2

---

## 🟢 Prioridad baja

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


