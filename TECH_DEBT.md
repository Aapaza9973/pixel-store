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

### Bug — Vista `historial.blade.php` faltante
- [ ] Crear `resources/views/admin/usuarios/historial.blade.php`
- Ruta `GET admin/usuarios/{user}/historial` devuelve 500 porque
  `UserController@historial()` hace `view('admin.usuarios.historial')`
  pero la vista no existe.
- **Detectado**: durante tarea 1.1.8 (commit 2e45c07)
- **Cuándo arreglarlo**: al cerrar HU-1.1 o al planificar módulo de
  auditoría individual

### Código muerto — UserController
- [ ] Eliminar alias `historialAccesos()` (sin ruta, sin vista, sin test)
- [ ] Eliminar claves duplicadas `'accesos'` y `'logs'` en `show()`
      (la vista solo usa `$ultimosAccesos`)
- **Detectado**: durante auditoría Bloque 0
- **Cuándo arreglarlo**: junto con el bug de historial (una vez creada
  la vista)

### Auditoría — homogeneizar try/catch best-effort
- [ ] Verificar que los 3 métodos de `AuditoriaService`
      (`registrarLoginExitoso`, `registrarLogout`, `registrarIntentoFallido`)
      no propaguen excepciones al usuario. Si sus callers (listeners)
      no los protegen, agregar `try/catch + report()` internos.
- [ ] Verificar lo mismo en `CheckUserHasRole` middleware
- **Detectado**: durante tarea 2.B (discrepancia entre la asunción del
  prompt y el código real)
- **Cuándo arreglarlo**: Sprint 2 al refactorizar auditoría

---

## 🟢 Prioridad baja

### Middleware en ruta de movimientos
- [ ] Agregar `->middleware('can:editar productos')` a la ruta
      `admin.movimientos.salida` en `routes/web.php`

**Actual**: cualquier usuario autenticado puede invocarla si conoce la URL.
**Riesgo**: bajo (no hay UI, requiere conocer la URL exacta, el service valida stock).

---

## Convención

- Cada ítem debe tener: **descripción clara** + **cuándo arreglarlo**
- Se revisa al inicio de cada sprint
- Los ítems completados se mueven a `CHANGELOG.md` con el commit que los cerró


