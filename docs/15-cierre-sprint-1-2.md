# 15 — Cierre de pendientes Sprint 1 y 2

**Fecha**: 2026-11-16
**Suite**: 428 tests passing (1.797 assertions) · 205 rutas
**Pint**: PASS

---

## ✅ Sprint 2 — pendientes cerrados

### Cotizaciones por correo y WhatsApp (HU-2.1b)

- `App\Mail\CotizacionEnviada` + vista `emails.cotizacion` con el detalle y el
  total.
- `POST admin/cotizaciones/{cotizacion}/enviar` valida el correo, envía y
  reporta el fallo sin romper la vista.
- En el detalle de la cotización hay un formulario de correo y un botón de
  WhatsApp (`wa.me`) con número, total y validez. No se adjunta el PDF porque
  esa ruta exige sesión: el correo lleva el detalle completo.

### Devoluciones con aprobación y puntos (HU-2.4b)

- `devoluciones.estado` ahora admite `pendiente`, `procesada`, `anulada` y
  `rechazada`; se agregaron `aprobada_por`, `aprobada_en` y `motivo_rechazo`.
- `DevolucionService`:
  - `solicitar()` registra la devolución **sin** mover stock, series ni puntos.
  - `aprobar()` reingresa stock, libera series, revierte puntos y recalcula el
    estado de la venta.
  - `rechazar()` deja constancia del motivo sin efectos de inventario.
- El cálculo de "unidades ya devueltas" cuenta solo `procesada`, así una
  solicitud pendiente no bloquea ni descuenta dos veces.
- Bandeja `admin/devoluciones` con filtro por estado y acciones Aprobar /
  Rechazar gated por `aprobar devoluciones` / `rechazar devoluciones`.

### Puntos de fidelidad

- `puntos` ya existía en el esquema pero nadie lo escribía.
- `VentaService::confirmar` acumula `floor(total / puntos.bs_por_punto)` puntos
  (config `config/puntos.php`, default 1 punto por Bs 10) cuando la venta tiene
  cliente.
- La devolución procesada o aprobada crea un `ajuste` negativo proporcional al
  monto devuelto, con el concepto `Devolución #id`.

### Número de serie obligatorio

- `VentaService::confirmar` ya lanzaba `DomainException` cuando el producto
  tiene `maneja_numero_serie` y el ítem no trae `numero_serie_id`; se verificó y
  se documentó. La bandera es por producto (más preciso que por categoría).

---

## ✅ Sprint 1 — pendientes cerrados

- Vista 403 migrada de `slate`/`blue` a tokens Obsidian
  (`bg-surface`, `text-on-surface`, `text-outline`). El dashboard ya estaba en
  Obsidian.
- **Capturas de pantalla** (1.1.18.5): siguen siendo material manual; no se
  generan desde código.

---

## 🧪 Evidencia

| Test | Cubre |
|---|---|
| `CotizacionEnvioTest` (2) | envío con `Mail::fake`, correo inválido |
| `DevolucionAprobacionTest` (5) | solicitud sin stock, aprobación con reingreso, rechazo, 403, bandeja |

Comandos:

```bash
php artisan test --filter="CotizacionEnvioTest|DevolucionAprobacionTest"
php artisan test
./vendor/bin/pint --dirty
```

**Resultado**: 428 tests passing (1.797 assertions), 205 rutas, Pint sin cambios.
