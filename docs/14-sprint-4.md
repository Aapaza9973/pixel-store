# 14 — Sprint 4: facturación, reportes avanzados y auditoría

**Fecha**: 2026-11-16
**Estado**: ✅ cerrado en modo simulación · ⏳ integraciones externas pendientes
**Suite**: 421 tests passing (1.778 assertions) · 200 rutas

---

## ✅ Entregado

| Bloque | Alcance | Evidencia |
|---|---|---|
| Facturación electrónica | `FacturacionService` (emitir/anular), `admin/facturacion`, CUF + XML simulados | `FacturacionTest` (5) |
| Pagos en línea | Pasarela simulada `pedido.pagar` con token, registra `pago` e `ingreso` | `PagoPedidoTest` (3) |
| Reportes avanzados | Comparativa mensual con variación; utilidad y antigüedad ya estaban en Indicadores | `ReporteComparativaTest` (2) |
| Auditoría | `admin/auditoria` con filtros (acción, usuario, fechas) y export CSV | `AuditoriaPanelTest` (4) |
| Proveedores / OC | Ya existían (PDF y recepción) | `OrdenCompra*Test` |

---

## 🔐 Decisiones

### La factura usa el esquema real del SIN, no uno propio

`facturas_electronicas` ya existía en `database/schema/pgsql-schema.sql` con
`cuf`, `cufd`, `xml_path`, `respuesta_sin` y el enum
`enum_factura_estado ('Pendiente','Emitida','Anulada','Error')`. La emisión
interna solo agregó `user_id` y `xml`; no se duplicó la tabla ni se inventó otro
estado.

### Emisión simulada, contrato definitivo

`FacturacionService` genera número correlativo (`F-2026-000001`) y un CUF de 49
caracteres derivado de hash, y guarda un XML con la venta. Cuando exista
credencial de CUCU/Libélula, se reemplazan `generarCuf()` y el envío, sin tocar
controladores ni vistas.

### Ingresos sin usuario

Un pedido web lo paga el comprador, no un empleado: la migración
`2026_11_16_000004` quita el `NOT NULL` de `ingresos.user_id`. Antes el cobro
online de invitados fallaba en la FK.

### Pago simulado explícito

`config/pagos.php` define `modo = simulado|produccion`. En `produccion` las
rutas responden 404 hasta que exista la pasarela: es preferible un 404 a un
cobro falso presentado como real. La pantalla avisa "Modo simulación".

---

## ⏳ Pendiente (requiere terceros)

1. **Stripe / PayPal**: SDK, claves, webhook firmado y conciliación.
2. **SIN (CUCU / Libélula)**: firma digital, CUFD, envío y código de recepción;
   el esquema ya tiene las columnas (`cufd`, `respuesta_sin`, `codigo_recepcion`).
3. **PDF fiscal**: hoy el detalle muestra el XML; el PDF con formato legal lo
   emite el proveedor.
4. **Filtros EAV** y búsqueda `pg_trgm` del catálogo (Fase 3).
5. **WhatsApp**: notificaciones salientes (Fase 3).
