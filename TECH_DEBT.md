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


