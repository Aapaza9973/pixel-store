# 16 — Validación manual del POS (los 5 puntos de `docs/10`)

**Fecha**: 2026-11-25
**Entorno**: `php artisan serve` en `http://127.0.0.1:8000`, PostgreSQL local, Chromium de escritorio (1152 × 939)
**Usuarios**: `vendedor@pixelstore.com` (Vendedor) y `admin@pixelstore.com` (Admin), contraseña `password`
**Motivo**: `docs/10` dejó cinco verificaciones que la suite PHP no puede hacer (Alpine, papel, polling). La retrospectiva de `docs/12` las señaló como el riesgo más probable de la demo.

**Resultado**: 5 de 5 verificadas. Tres hallazgos, todos corregidos en esta misma sesión.

---

## 1. Descuento en vivo y tope por rol ✅

| Paso | Esperado | Observado |
|---|---|---|
| Carrito 4.250,00 → descuento 5 % | Total baja y el saldo se recalcula | Descuento `− Bs 212,50`, total y saldo pendiente `Bs 4.037,50` |
| Escribir 15 % (tope Vendedor = 10 %) | El sistema no permite pasarse | El descuento vuelve a `Bs 0,00`, el total a `Bs 4.250,00` y aparece «Descuento requiere autorización de administrador» con el botón «Solicitar autorización» |

El recálculo es inmediato, sin recargar, y el tope se aplica en pantalla (además del control del servidor).

## 2. Modal de número de serie ✅ (con hallazgo)

| Paso | Esperado | Observado |
|---|---|---|
| Agregar un producto con `maneja_numero_serie` | La línea muestra «Elegir serie» | Sí, en el paso 2 (Carrito) |
| Abrir el modal | Lista de series disponibles | Se abre; **la base demo no tenía ninguna serie**, así que mostraba «Este producto no tiene series disponibles» |

**Hallazgo 1**: ningún seeder creaba `numeros_serie`. En una instalación limpia, cualquier venta de un producto serializado queda bloqueada: el vendedor no puede cobrar. Se agregó `database/seeders/NumeroSerieSeeder.php` (una serie `disponible` por unidad en stock, ubicación Tienda, idempotente con `firstOrCreate`) y se registró en `DatabaseSeeder` después de `DemoProductoSeeder`. Generó 35 series en la base local.

Tras eso, el modal lista `GPU-AMD-RX6600-0001…0011`, se elige una y la línea queda con su serie.

## 3. Pago mixto ✅

| Paso | Esperado | Observado |
|---|---|---|
| Efectivo 2.000 | Saldo parcial y «Confirmar venta» deshabilitado | Total pagado `Bs 2.000,00`, saldo `Bs 2.037,50`, botón deshabilitado |
| Tarjeta 2.037,50 (botón «Pago exacto» con método Tarjeta) | Saldo 0 y botón habilitado | Saldo `Bs 0,00`, botón habilitado |
| Confirmar | Venta creada con sus dos pagos | Venta **#23**, estado `Pagado`, total `4.037,50`, pagos `Efectivo 2.000,00` + `Tarjeta 2.037,50` |

Efectos verificados en la base: stock del Ryzen 5 5600X 18 → 17, stock de la RX 6600 11 → 10, serie `GPU-AMD-RX6600-0001` en estado `vendido` con `venta_id = 23`.

## 4. Impresión de ambos comprobantes ✅

| Comprobante | Esperado | Observado |
|---|---|---|
| Ticket 80 mm | `@page size: 80mm auto`, número, ítems, serie y total | Regla `size: 80mm auto; margin: 4mm`, ticket `#000023`, SN en la línea, descuento y total `Bs 4.037,50`, pagos por método |
| A4 | `@page size: A4` y maquetación de hoja | Regla `size: A4; margin: 20mm`, tabla con cantidad/precio/subtotal y los mismos totales |

Capturas: `02-comprobante-ticket-80mm.png` y `03-comprobante-a4.png`. No se probó una impresora física: se verificó la regla de paginación y el contenido renderizado.

## 5. Polling de inventario ✅ (con hallazgo)

| Paso | Esperado | Observado |
|---|---|---|
| Abrir `/admin/inventario` | Cuatro indicadores con datos | **Salían vacíos** y la consola mostraba `Alpine Expression Error: Unexpected token '}'` |

**Hallazgo 2**: el `x-data` se armaba con `@js([...])` dentro de un atributo citado; el HTML quedaba cortado (`x-data="inventarioPoll(JSON.parse(" {\u0022productostotales…`) y Alpine no inicializaba el componente. Los KPIs nunca se pintaban y el `setInterval` de 30 s no llegaba a registrarse. Se reemplazó por un literal armado en Blade (`x-data="inventarioPoll({ productosTotales: {{ (int) $productosTotales }}, …, url: '…' })"`).

Tras el arreglo: `15 · 0 · 0 · Bs 327.180,00` y, al cambiar el stock por fuera de la app, el indicador pasó a `Bs 332.980,00` **sin recargar la página** (espera de 35 s = un ciclo).

Alcance real del polling: refresca los cuatro indicadores (totales, stock bajo, agotados, valor). **No** refresca las filas de existencias por producto, que es como estaba redactado el punto 5 de `docs/10`; queda anotado como decisión pendiente, no como bug.

---

## Hallazgo 3 — la ficha de producto ocultaba sus especificaciones

Al revisar el catálogo con los filtros EAV nuevos, la ficha seguía mostrando el texto «La tabla de especificaciones se publica cuando el catálogo técnico esté completo (HU-2.4)» aunque el producto tuviera atributos cargados. Ahora la ficha lista los atributos EAV del producto (`SOCKET`, `CHIPSET`, `TIPO RAM`…), ordenados por `atributos_tecnicos.orden`, y omite el bloque si no hay datos. Cubierto por `CatalogoFiltrosTest::test_ficha_muestra_las_especificaciones_del_producto`.

---

## Capturas

Están en `docs/capturas/` y cubren el pendiente 1.1.18.5 del Sprint 1 («capturas de pantalla»), que llevaba dos sprints abierto:

| Archivo | Pantalla |
|---|---|
| `01-pos-cobro-mixto.png` | POS, paso 3: descuento 5 %, pagos 2.000 + 2.037,50, saldo 0 |
| `02-comprobante-ticket-80mm.png` | Comprobante 80 mm de la venta #23 |
| `03-comprobante-a4.png` | Comprobante A4 de la venta #23 |
| `04-dashboard.png` | Dashboard con los indicadores del inventario |
| `05-catalogo-filtro-socket-am4.png` | Catálogo filtrando por categoría + `socket = AM4` |
| `06-ficha-producto-especificaciones.png` | Ficha con la tabla de especificaciones EAV |

## Qué no cubre esta validación

- No se probó impresión física ni el corte real del papel de 80 mm.
- El flujo de **autorización de descuento por un administrador** se verificó solo en su primer paso (el aviso y el botón); aprobar la solicitud desde otro usuario no se recorrió.
- El polling se verificó con un cambio de stock externo; no se midió el costo de la consulta con un catálogo grande.
- Navegador único (Chromium de escritorio); no se probó en móvil.
