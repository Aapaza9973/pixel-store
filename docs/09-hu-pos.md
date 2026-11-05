# 09 — HU 5.5 / 5.6 / 5.8: POS (cliente, series, multi-pago y buscador)

Documento de referencia del Punto de Venta: qué resuelve cada historia, qué
endpoints consume la pantalla, qué permisos hacen falta y qué tests lo cubren.

> **Nota de numeración**: este archivo comparte el prefijo `09` con
> [`09-roadmap.md`](09-roadmap.md) porque así lo pidió el sprint (D5-C).

---

## 1. Alcance

| HU | Tema | Estado |
|:-:|---|---|
| 5.5 | Cliente en la venta (búsqueda + alta rápida) | ✅ |
| 5.6 | Números de serie (modal de selección y liberación) | ✅ |
| 5.8 | Pago múltiple / mixto | ✅ |
| 5.3 | Buscador de productos del POS | ✅ (soporte de las tres) |

Pantalla: `resources/views/admin/ventas/create.blade.php`
(Alpine.js + Tailwind, sin librerías de POS externas).
Confirmación: `VentaController@store` → `VentaService::confirmar()`.
El detalle y el comprobante viven en `admin/ventas/show` y `admin/ventas/comprobante`.

**Regla transversal**: el POS nunca envía precios ni totales. Envía
`producto_id` + `cantidad`, y el servidor recalcula subtotal, descuento y total
con los precios de la base, bajo lock. El importe de los pagos sí lo escribe el
cajero, pero solo se valida contra el total del servidor.

---

## 2. Cliente en la venta (HU 5.5)

### Qué hace
- **Selector de cliente**: busca por nombre, NIT/CI o teléfono (`?q=`) y deja
  elegido el cliente para la venta (`meta.cliente_id`).
- **Alta rápida**: abre un modal, crea el cliente y lo deja seleccionado sin
  salir del POS. Solo aparece si el usuario puede crear clientes
  (`@can('crear clientes')`) — el Cajero ve el buscador, no el alta.
- **Sin cliente**: la venta se registra con `cliente_id = null`
  (consumidor final).

### Endpoints
| Acción | Verbo y ruta | Nombre de ruta |
|---|---|---|
| Buscar cliente | `GET /admin/clientes/buscar?q=` | `admin.clientes.buscar` |
| Alta rápida | `POST /admin/clientes` (JSON → 201) | `admin.clientes.store` |

`ClienteController@store` responde JSON cuando `expectsJson()`, con
`{message, data:{id, nombre, nit_ci, telefono, email}}`.

### Tests
- `StoreVentaTest::test_venta_con_cliente_id_persiste_el_cliente`
- `StoreVentaTest::test_venta_sin_cliente_id_permite_confirmar`
- `VentaPosTest::test_pos_oculta_cliente_rapido_para_cajero`

---

## 3. Números de serie (HU 5.6)

### Qué hace
- Si el producto tiene `maneja_numero_serie`, la línea del carrito muestra
  **Elegir SN**; el modal lista solo series `disponible` del producto.
- La serie viaja como `items[].numero_serie_id`. Al confirmar, `VentaService`
  la valida **dentro de la transacción** (`NumeroSerieService::validarParaVenta`)
  y la marca `vendido` con `venta_id`, `cliente_id` y `fecha_venta`.
- Una devolución libera la serie: vuelve a `disponible` y se desliga de la
  venta (`DevolucionService::reingresarStock`), aun cuando la devolución sea
  parcial (la venta queda `parcialmente_devuelta`).

### Endpoints
| Acción | Verbo y ruta | Nombre de ruta |
|---|---|---|
| Series disponibles | `GET /admin/productos/{producto}/series` | `admin.productos.series` |

Respuesta: `{ data: [{ id, numero_serie }] }`, máximo las disponibles.

### Tests
- `StoreVentaTest::test_venta_con_numero_serie_id_marca_la_serie_como_vendida`
- `StoreVentaTest::test_endpoint_series_devuelve_solo_disponibles`
- `StoreVentaTest::test_endpoint_series_rechaza_sin_permiso`
- `StoreVentaTest::test_venta_sin_numero_serie_id_sigue_funcionando`
- `DevolucionControllerTest::test_store_devolucion_parcial_actualiza_estado_y_libera_serie`
- `VentaServiceTest::test_confirmar_valida_sn_si_se_especifica`,
  `test_confirmar_marca_sn_como_vendido`

---

## 4. Pago múltiple (HU 5.8)

### Qué hace
- El cajero agrega **N pagos**, cada uno con `metodo`
  (`Efectivo|Tarjeta|Transferencia|QR|Otro`), `monto` y `referencia` opcional
  (nro. de voucher, transferencia, etc.).
- Regla del servidor: `suma(pagos) >= total`. Se acepta pago de más porque en
  efectivo puede haber vuelto; si falta dinero → **422** `pago_insuficiente`.
- Cada pago se guarda en `pagos` con `estado = 'Completado'` dentro de la misma
  transacción de la venta (junto con `ingresos`).
- Antes de confirmar, el POS revalida stock con un endpoint de cortesía; la
  garantía real sigue siendo el lock de `VentaService` (**409** si no alcanza).

### Endpoints
| Acción | Verbo y ruta | Nombre de ruta |
|---|---|---|
| Confirmar venta | `POST /admin/ventas` | `admin.ventas.store` |
| Revalidar stock | `POST /admin/ventas/validar-stock` | `admin.ventas.validar-stock` |
| Autorizar descuento | `POST /admin/ventas/autorizar-descuento` | `admin.ventas.autorizar-descuento` |
| Comprobante | `GET /admin/ventas/{venta}/comprobante` | `admin.ventas.comprobante` |

Payload de confirmación (resumen):

```json
{
  "items": [{ "producto_id": 1, "cantidad": 1, "numero_serie_id": null }],
  "pagos": [
    { "metodo": "Efectivo", "monto": 600 },
    { "metodo": "Tarjeta", "monto": 400, "referencia": "POS-123" }
  ],
  "descuento_porcentaje": 0,
  "meta": { "operacion_id": "uuid", "cliente_id": null }
}
```

`operacion_id` es la **clave de idempotencia**: reintentar con el mismo valor
devuelve la misma venta (no duplica stock, pagos ni ingresos).

### Tests
- `StoreVentaTest::test_multi_pago_suma_correcta_permite_confirmar`
- `StoreVentaTest::test_multi_pago_con_suma_insuficiente_devuelve_422`
- `StoreVentaTest::test_multi_pago_persiste_la_referencia`
- `StoreVentaTest::test_confirmar_registra_ingreso_economico`
- `StoreVentaTest::test_reintento_con_mismo_operacion_id_no_duplica_venta_ni_descuento`
- `StoreVentaTest::test_idempotencia_100_reintentos_crea_una_sola_venta`
- `ValidarStockTest` (revalidación previa a confirmar)
- `AutorizarDescuentoTest` (descuento fuera del límite del rol)
- `VentaServiceConcurrenciaTest` (dos ventas del último ítem: solo una gana)
- `ComprobanteTest` (impresión del comprobante)

---

## 5. Buscador de productos (soporte de 5.3)

### Qué hace
- Caja de búsqueda con debounce que consulta por **nombre, SKU o código de
  barras**, devuelve hasta **10** productos y muestra precio, stock y si
  maneja número de serie.
- Al agregar, el carrito toma **stock real** del producto para limitar la
  cantidad; al confirmar, el servidor vuelve a validar bajo lock.

### Endpoints
| Acción | Verbo y ruta | Nombre de ruta |
|---|---|---|
| Buscar productos | `GET /admin/ventas/buscar-productos?q=` | `admin.ventas.buscar-productos` |

Throttle: **60 requests/minuto**. Respuesta:
`{ data: [{ id, nombre, sku, precio_unitario, stock, maneja_numero_serie, categoria, marca }] }`.

### Tests
- `VentaPosTest::test_pos_muestra_buscador_de_productos`
- `VentaPosTest::test_pos_incluye_wiring_del_buscador`
- `VentaPosTest::test_buscar_productos_requiere_permiso_ver_productos`
- `VentaPosTest::test_buscar_productos_incluye_maneja_numero_serie`
- `VentaPosTest::test_pos_guarda_stock_en_la_linea_del_carrito`

---

## 6. Permisos requeridos

| Acción | Permiso / regla |
|---|---|
| Abrir el POS (`admin.ventas.create`) | sesión + rol asignado (`user.has.role`); no tiene permiso propio |
| Confirmar venta | `crear ventas` (`StoreVentaRequest@authorize`) |
| Revalidar stock | `crear ventas` (`ValidarStockRequest@authorize`) |
| Buscar productos / series | `ver productos` (policy de `Producto`, `viewAny`) |
| Buscar clientes | `ver clientes` |
| Alta rápida de cliente | `crear clientes` |
| Autorizar descuento | `crear ventas` + credenciales válidas de un usuario con rol **Admin** (throttle 5/min) |
| Ver detalle / comprobante | `ver ventas` |
| Registrar devolución | `crear devoluciones` |

Límite de descuento por rol (`roles.descuento_maximo`, HU 5.10): Admin 100 %,
Vendedor 10 %, Cajero 5 %, Inventario/Cliente 0 %. Superarlo sin autorización
→ **422**; con `autorizacion_id` válida se aplica y queda `autorizado_por`.

---

## 7. Mapa de errores del POS

| Situación | HTTP | `code` |
|---|---|---|
| Stock insuficiente | 409 | `stock_insuficiente` |
| Número de serie no disponible | 409 | `serie_no_disponible` |
| Pago insuficiente | 422 | `pago_insuficiente` |
| Descuento sobre el límite del rol | 422 | `domain_error` |
| Validación de payload (FormRequest) | 422 | `errors` |

El POS traduce el 409 a un aviso de stock y el 422 al mensaje del servidor; el
servicio nunca responde 500 por una regla de negocio.

---

## 8. Deudas conocidas

- El número de comprobante se deriva del PK (`#000123`): falta numeración
  correlativa por sucursal.
- La devolución usa un motivo por cabecera; el motivo por línea llega en 5.15.
- El contador de favoritos de la navbar vive en `layouts.app`
  (`FavoritoService::listar()`), visible solo en rutas `catalogo.*`.
