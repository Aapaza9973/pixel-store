# 12 — Demo y retrospectiva del Sprint 2 (D10)

**Fecha**: 2026-11-05
**Duración de la demo**: 5 minutos
**Base**: el guion sigue `tests/Feature/SprintE2ETest.php`, que ya recorre este
mismo camino por HTTP (35 assertions).

---

## 🎬 Guion de demo (5 min)

### Preparación (antes de entrar)

- Un producto **con número de serie**: SSD 600 Bs, stock 3, con una serie
  `disponible`.
- Un producto **sin serie**: mouse 100 Bs, stock 5.
- Usuario **Vendedor** (`vendedor@pixelstore.com`) y usuario **Admin**
  (`admin@pixelstore.com`). La caja y los reportes se muestran con Admin
  porque `Caja` está restringida a Admin/Cajero.

Totales esperados: subtotal 700 → descuento 5 % = 35 → **total 665**.
Pagos: **400 Efectivo + 265 Tarjeta**.

| Min | Paso | Qué hacer | Qué debe verse |
|:-:|---|---|---|
| 0:00 | Login vendedor | Entrar con el usuario Vendedor | Redirección al dashboard/POS |
| 0:15 | Abrir POS | Ir a **Nueva venta** | Buscador, carrito vacío y tope de descuento 10 % |
| 0:30 | Agregar productos | Buscar el SSD y el mouse, agregarlos | Dos líneas; el SSD muestra **Elegir serie** |
| 0:50 | Elegir serie | Abrir el modal del SSD y elegir la serie | La línea muestra el SN asignado |
| 1:05 | Descuento | Aplicar **5 %** | Total baja a 665 y el botón sigue habilitado (5 % ≤ 10 %) |
| 1:20 | Pago mixto | Agregar Efectivo 400 y Tarjeta 265 | Saldo pendiente en 0 y **Confirmar venta** habilitado |
| 1:40 | Confirmar | Cobrar | Mensaje de venta confirmada con número; stock del SSD 3→2 y mouse 5→4 |
| 2:00 | Comprobante ticket | Abrir **Ticket** | Vista de 80 mm con número `#000123`, ítems, SN y **TOTAL 665,00** |
| 2:20 | Comprobante A4 | Cambiar a **A4** | Misma venta en hoja A4; con `@page size: A4` al imprimir |
| 2:40 | Detalle | Volver a la venta | Líneas, pagos y botón **Devolver** |
| 3:00 | Devolución | Devolver **1 unidad del SSD con serie** | Estado **parcialmente devuelta**; la serie vuelve a `disponible`; stock 2→3 |
| 3:40 | Cambio de usuario | Logout y login como **Admin** | — |
| 3:50 | Cierre de caja | Abrir **Caja** del día | Resumen por método: Efectivo 400 y Tarjeta 265 |
| 4:20 | Reporte período | Abrir **Reportes** con el día de hoy | Ventas, total y ranking del día |
| 4:40 | PDF | Exportar PDF (tipo vendedor) | Descarga `application/pdf` con el reporte |

**Consejo de demo**: si algo falla en el camino, no improvisar datos; anotar el
paso y el mensaje. El E2E cubre el mismo recorrido y sirve para reproducirlo.

---

## 🔁 Retrospectiva

### ✅ Qué funcionó

1. **El E2E como red de seguridad real.** Recorrió login → venta → comprobante
   → devolución → caja → reportes → PDF en 1,5 s y pasó a la primera, sin tocar
   código de producción. Fue el mejor indicador de que los módulos encajan.
2. **Disciplina de no tocar tests viejos.** Los 9 cierres agregaron tests
   nuevos y la suite completa se mantuvo verde: **0 regresiones** en todas las
   corridas (369 tests finales).
3. **Concurrencia medida, no declarada.** `Venta100ConcurrenciaTest` corre 100
   procesos reales con barrera común y resultó estable **10/10**; el
   `lockForUpdate` + `CHECK (stock >= 0)` sostienen la garantía de no-oversell.
4. **Decisiones escritas.** D2 (`autorizado_por` es solo registro) y D3 (el
   vuelto se calcula en el cierre) cerraron discusiones que iban a reaparecer.
5. **El gate de 92** siguió siendo una lista auditable y barata de correr antes
   de cada cierre.

### ⚠️ Qué no funcionó (honesto)

1. **El comprobante no tenía formato de impresión y nadie lo vio hasta el
   final.** El E2E solo verificaba 200 y el número, no el `@page`; el gap 5.12.2
   apareció recién al cerrar. Un test de “papel” habría costado cinco líneas.
2. **Durante semanas se reportó “concurrencia” con tests secuenciales.** El
   smoke de 2 procesos era un buen ejercicio de lock, pero no concurrencia de
   carga; recién 5.13.12 la midió de verdad.
3. **La UI Alpine no tiene cobertura automática.** Modal de serie, descuento en
   vivo y multi-pago se validaron por contrato de payload, no en navegador. La
   validación manual sigue pendiente y es el riesgo más probable de la demo.
4. **La numeración de HU es ambigua.** “2.5” es catálogo en el código y cierre
   de caja en el roadmap; hubo que aclararlo en el QA final. Sin backlog
   versionado, HU-2.7/2.8 no eran verificables.
5. **Deudas funcionales silenciosas.** Compras no tiene el paso “enviar orden”:
   una OC nace en `borrador` y la recepción no es alcanzable desde la UI aunque
   el backend y el modal existan.

### 🎯 Acciones para Sprint 3

1. **Smoke visual con navegador (Playwright)** del POS: serie obligatoria,
   descuento en vivo, multi-pago y ambos comprobantes. Es el hueco que la suite
   PHP no puede tapar.
2. **Backlog versionado con IDs únicos de HU** y checklist de cierre por HU,
   para que código, roadmap y QA hablen del mismo número.
3. **Cerrar el flujo de compras**: enviar/cancelar orden y recepción end-to-end
   desde la UI, con test de integración.
4. **Extender el E2E a impresión**: assert de `@page size` en ticket y A4, para
   que un cambio de vista no rompa el papel sin que falle nada.
5. **Presupuestar el costo del test de 100 procesos** (~9 s y 100 procesos del
   SO por corrida) y decidir si corre en cada push o solo en el gate de cierre.

### 📊 Métricas del cierre

| Métrica | Valor |
|---|---|
| Tests finales | **369 passing (1.600 assertions)** |
| Gate de autenticación/permisos | 92 passing (311 assertions) |
| Regresiones | **0** |
| Commits de cierre | **10** (2 de QA/documentación + 7 de gaps post-tag + este D10) |
| Gaps cerrados después del tag | 7: 5.6.7, 5.7.3, 3.4.9, 3.4.10, 5.12.2, 5.13.12, 2.8.4 |
| Estabilidad del test de 100 procesos | 10/10 corridas verdes (~9 s c/u) |
