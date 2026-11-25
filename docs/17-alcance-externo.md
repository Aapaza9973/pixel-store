# 17 — Pendientes que quedan fuera y decisiones cerradas (2026-11-25)

Cierre de los huecos del Sprint 1 y 2 que **no** se resolvieron con código en esta sesión, con el motivo y el estado. Todo lo demás quedó implementado o documentado en `docs/15` y `docs/16`.

---

## 1. Facturación electrónica real (SIN — CUCU / Libélula)

**Estado**: fuera de alcance, bloqueado por terceros.
**Qué falta**: firma digital, CUFD, envío del XML y código de recepción del SIN.
**Qué ya existe**: `facturas_electronicas` con el esquema real (`cuf`, `cufd`, `xml_path`, `respuesta_sin`, `codigo_recepcion`), `FacturacionService` en modo simulación y `admin/facturacion` con el aviso de simulación.
**Decisión**: no se toca hasta que el negocio contrate el proveedor y entregue credenciales. Una factura simulada no tiene validez tributaria y la pantalla lo advierte; no se presenta como real.

## 2. PDF fiscal

**Estado**: fuera de alcance.
**Motivo**: el PDF con formato legal lo emite el proveedor del SIN. Hoy el detalle muestra el XML generado, que es suficiente para verificar el contrato mientras no haya proveedor.

## 3. Notificaciones salientes por WhatsApp

**Estado**: fuera de alcance.
**Motivo**: requiere cuenta de WhatsApp Business, número verificado y plantillas aprobadas; sin eso, cualquier envío es un mensaje no autorizado.
**Alternativa en curso**: el ciclo de vida del pedido se notifica por correo (HU-S3-03 del `Backlog Sprint 3.xlsx`), que ya cubre pago aprobado, cancelación, recordatorio de vencimiento y aviso interno.

## 4. Rama `b52d895` / `respaldo-shop` (segunda implementación del módulo Shop)

**Estado**: decisión cerrada.
**Decisión**: se queda la implementación integrada en `main` (`CatalogoController`, `CheckoutController`, `VentaService::crearPedidoOnline`), que es la que tiene tests y está documentada en `docs/13`. La rama `respaldo-shop` se conserva como respaldo y **no se rebasa**. En el working tree no existen `app/Contracts`, `app/DTOs`, `app/Services/Cart` ni `app/Http/Controllers/Shop`, así que no hay dos módulos compitiendo hoy.

## 5. Burndown y registro de horas reales

**Estado**: no se puede reconstruir hacia atrás.
**Motivo**: ningún sprint registró horas reales; las de los cronogramas son estimaciones. Reconstruirlas desde git sería una ficción.
**Decisión**: el Sprint 3 no inventa un burndown histórico. El cronograma ya mide por **gate** (G9–G16) y por día objetivo; si el equipo quiere horas reales, se registran desde D1 con una columna nueva en la hoja de seguimiento.

## 6. Corrección de diagnóstico: `composer.lock` no está desalineado

`composer.json` pide `spatie/laravel-permission: "8.3"` y el lock trae `8.3.0`. Es una restricción satisfecha, no una inconsistencia. Se retira de la lista de deudas.

---

## Lo que sí quedó cerrado con código (resumen)

| Hueco | Cómo se cerró | Evidencia |
|---|---|---|
| Filtros EAV del catálogo | `atributos[id]=valor` en `/catalogo`, con deduplicación por nombre y límite de 6 filtros | `CatalogoFiltrosTest` (6) |
| Búsqueda `pg_trgm` | `Producto::scopeBuscar` con `unaccent` y `similarity`, más escape de comodines | `CatalogoFiltrosTest::test_busqueda_*` |
| Especificaciones en la ficha | Tabla EAV en `catalogo/producto.blade.php` | Test de la ficha + captura 06 |
| Compras: «Enviar orden» | Ya existía en la UI; se agregó el test de la ruta y se corrigió el comentario obsoleto del servicio | `OrdenCompraUiTest` (4) |
| Compras: recepción desde la UI | Test de integración del POST del modal | `OrdenCompraRecepcionTest` (6) |
| Series en la base demo | `NumeroSerieSeeder` + registro en `DatabaseSeeder` | 35 series creadas |
| KPIs de inventario vacíos | `x-data` reemplazado por literal de Blade | Verificado en navegador (331.530 → 332.980 sin recargar) |
| Logo blanco | `public/images/logo/pixel-logo-white.png` generado con GD desde el logo horizontal | Verificado sobre fondo `#0a0e14` |
| Encuesta borrada sin commit | Borrado coherente: modelo, controladores, vistas, ruta, permiso, test y migración `drop_encuestas_table` | `docs/09` y `docs/13` actualizados |
| Documentación desactualizada | `docs/09` y `docs/13` corregidos; numeración del «Sprint 3» vieja explicada | Este documento y `docs/13` |
| Capturas de pantalla (1.1.18.5) | Seis capturas en `docs/capturas/` | `docs/16` |
| Validación manual del POS | Cinco puntos de `docs/10` recorridos | `docs/16` |
