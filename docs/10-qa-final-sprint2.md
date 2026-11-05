# 10 — QA final y cierre del Sprint 2

**Fecha de cierre**: 2026-11-05
**Rama**: `feature/s2-foundation` (sin merge a `main`; esa decisión queda para el usuario)
**Alcance**: POS, devoluciones, cierre de caja, reportes, cotizaciones, clientes y números de serie.

El cierre se apoya en tres capas: los tests por módulo (existentes), un test E2E
nuevo que recorre el happy path completo y la verificación de comandos de abajo.
No se agregaron funcionalidades en este cierre.

---

## ✅ Checklist de aceptación

| HU / RF | Alcance | Estado | Evidencia |
|---|---|:-:|---|
| HU-2.1 | Cotizaciones: alta, PDF, validez configurable, estados, conversión a venta y anulación | ✅ | `CotizacionTest` (6), rutas `cotizaciones.{index,create,store,show,pdf,convertir,anular}` |
| HU-2.1b | Envío automático de cotizaciones por WhatsApp/correo | ⚠️ | No implementado: el PDF se descarga o comparte manualmente |
| HU-2.2 | Números de serie: servicio, vinculación al vender, liberación al devolver y estados | ✅ | `NumeroSerieServiceTest` (5), `DevolucionControllerTest`, `DevolucionServiceTest` |
| HU-2.2b | CRUD de números de serie y consulta de garantía por SN | ⚠️ | Sin controller ni vistas propias; solo servicio |
| HU-2.4 | Devoluciones: reingreso de stock, liberación de serie, estados parcial/total, idempotencia | ✅ | `DevolucionServiceTest`, `DevolucionControllerTest`, `DevolucionStockTest`, `DevolucionConcurrenciaTest`, `DevolucionSchemaTest` |
| HU-2.4b | Solicitud/aprobación y reversión proporcional de puntos | ⚠️ | La devolución se registra `procesada`; no hay flujo de aprobación ni puntos |
| HU-2.5 (roadmap) | Cierre de caja: resumen del día, cierre único por vendedor/día, reporte diario | ✅ | `CierreCajaServiceTest`, `CierreCajaControllerTest` (HU-6.1 en código) |
| HU-2.5b | Exportación PDF del cierre de caja | ⚠️ | No hay ruta/PDF de cierre; sí reporte diario en pantalla |
| HU-2.6 | Reportes: período, vendedor, categoría, métodos de pago, indicadores, PDF y Excel | ✅ | `ReporteServiceTest` (10), `ReportePdfTest` (2), `ReporteController` |
| HU-5.3 / 5.5 / 5.6 | POS: búsqueda, carrito, selector de número de serie | ✅ | `VentaPosTest` (12), `ValidarStockTest` |
| HU-5.10 / 5.11 | Descuentos por rol y autorización de administrador | ✅ | `DescuentoServiceTest`, `AutorizarDescuentoTest`, `StoreVentaTest` |
| HU-5.13 / 5.14 | Ingreso por venta, comprobante y devoluciones desde el detalle | ✅ | `ComprobanteTest`, `VentaServiceTest` (13), `DevolucionControllerTest` |
| HU-6.1 | Cierre de caja y reporte diario | ✅ | `CierreCajaControllerTest` |
| HU-6.2 – 6.5 | Reportes por período/específicos, índices, utilidad y exportaciones | ✅ | `ReporteServiceTest`, `ReportePdfTest` |
| Clientes | CRUD, historial y puntos | ✅ | `ClienteTest` (10) |
| RF.3.2 – 3.5 | Carrito/checkout público, pedidos online, pagos en línea y encuesta anónima | ❌ | No existen rutas, controladores ni modelos: siguen en Fase 3 del roadmap |
| E2E de cierre | Login → venta → comprobante → devolución → caja → reportes → PDF → logout | ✅ | `SprintE2ETest` (1 test, 35 assertions) |

> El backlog del Sprint 2 no está versionado en el repo, así que los códigos HU
> se toman de los comentarios del código y de `docs/09-roadmap.md`. Por eso dos
> filas del pedido (HU-2.7 y HU-2.8) no aparecen: **no hay referencia a ellas en
> código, tests ni docs**, y no se marcaron como completas sin evidencia.

---

## 🖥️ Validación manual pendiente en navegador

PHP no ejecuta Alpine ni el JS del POS, así que estos puntos requieren una
pasada manual (no bloquean el cierre; los tests pinean el contrato del payload):

1. **Descuento en vivo**: que el total y el saldo pendiente se recalculen al mover el slider y que el tope del rol (10 % Vendedor, 5 % Cajero) se respete en pantalla.
2. **Modal de número de serie**: abrir el selector al agregar un producto serializado y verificar que no permita confirmar sin serie.
3. **Multi-pago**: agregar Efectivo + Tarjeta, ver el saldo pendiente y confirmar que el botón de cobro se habilita solo al cubrir el total.
4. **Impresión**: comprobante en 80 mm y A4 (márgenes, corte y legibilidad del número `#000123`).
5. **Polling de inventario**: que las existencias del listado se refresquen sin recargar (HU-4.5).

---

## 🧪 Comandos de verificación

```bash
# Gate de autenticación/permisos (HU-1.1 / HU-RF.4): 92 tests
php artisan test --testsuite=gate

# POS: 12 tests
php artisan test --filter=VentaPos

# Cierre E2E del sprint
php artisan test --filter=SprintE2E

# Suite completa: 355 tests
php artisan test

# Formato
./vendor/bin/pint --dirty
```

**Resultado del cierre**: 355 tests passing (1.535 assertions), 92 del gate,
12 de `VentaPosTest`, 1 E2E. `pint --dirty` sin cambios pendientes.

---

## 🧭 Decisiones de negocio (no son deuda)

### D2 · `autorizado_por` es solo registro

El campo `ventas.autorizado_por` guarda **quién** autorizó, pero por sí solo
**no habilita** un descuento por encima del límite del rol. La habilitación real
es el flujo de autorización (`autorizaciones_descuento` + `autorizacion_id`),
que valida monto, porcentaje y operación dentro de la transacción de la venta.

Si en Sprint 3 se necesita un descuento permanente o previo, se implementa con
una UI de **“solicitar autorización”** y su bandeja de aprobación; no cambiando
el significado de la columna.

### D3 · El vuelto no se persiste como campo

No existe una columna de vuelto. El POS acepta pagos por encima del total y el
vuelto se calcula al vuelo en el **cierre de caja**:

```
vuelto = efectivo_real - efectivo_esperado
```

Así el cierre refleja el faltante/sobrante real del turno sin duplicar el dato
en cada venta ni arriesgar inconsistencias si se corrige un pago.

---

## 🔀 Concurrencia real (5.13.12)

5.13.12 implementado como `Venta100ConcurrenciaTest` (100 procesos). El smoke de 2 procesos se mantiene como `VentaServiceConcurrenciaTest`.

---

## ⚠️ Limitaciones conocidas

- `Venta100ConcurrenciaTest` levanta 100 procesos PHP reales; el resto de la suite es secuencial (PHPUnit no paraleliza por sí solo). La garantía de no-oversell sigue siendo el `lockForUpdate` más el `CHECK (stock >= 0)`.
- Sin carrito/pedidos públicos ni pagos en línea (Fase 3).
- Números de serie sin CRUD ni consulta de garantía por SN.
- El E2E cubre el happy path; los caminos de error siguen cubiertos por los tests por módulo.
