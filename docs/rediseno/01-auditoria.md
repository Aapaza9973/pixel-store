# Auditoría del frontend actual — Pixel Store

> **Estado:** inventario real, generado a partir de `resources/views/**/*.blade.php`.
> **Turno:** auditoría (no se modificó ninguna vista).
> **Documento hermano:** `docs/rediseno/00-plan.md`.
> **Referencia de marca:** `skills/frontend-design.md`.

Comando usado para el inventario:

```bash
find resources/views -name "*.blade.php" | sort   # 47 archivos
```

---

## 1. Inventario real (47 vistas)

**Leyenda de prioridad:**
🔴 Alta = vista central del panel / usuario la ve a diario
🟡 Media = vista secundaria o de flujo puntual
🟢 Baja = vista poco visible o de bajo impacto visual
⚫ Nula = vista muerta o no productiva (revisar/eliminar)

**Tipos:** listado / formulario / detalle / dashboard / auth / error / layout / componente

| # | Vista | Tipo | Complejidad | Prioridad | Notas |
|---|---|---|---|---|---|
| 1 | `admin/productos/index` | Listado | Alta | 🔴 Alta | Tabla + 6 filtros + chips + Alpine + paginación. Máxima densidad de UI |
| 2 | `admin/productos/create` | Formulario | Alta | 🔴 Alta | Secciones, file input, Alpine (`productoForm`), atributos dinámicos, `@error` |
| 3 | `admin/productos/edit` | Formulario | Alta | 🔴 Alta | Espejo de create + valores actuales |
| 4 | `admin/productos/show` | Detalle | Alta | 🔴 Alta | Cards, stock por ubicación, modal de transferencia (Alpine) |
| 5 | `admin/productos/partials/atributos` | Componente | Media | 🟡 Media | Parcial con campos condicionales según categoría |
| 6 | `admin/ubicaciones/index` | Listado | Media | 🟡 Media | Dos tablas agrupadas (Tienda / Depósito) |
| 7 | `admin/ubicaciones/create` | Formulario | Baja | 🟢 Baja | Formulario simple |
| 8 | `admin/ubicaciones/edit` | Formulario | Baja | 🟢 Baja | Formulario simple |
| 9 | `admin/usuarios/index` | Listado | Media | 🔴 Alta | Tabla con avatar, chips de rol, estado, acciones |
| 10 | `admin/usuarios/create` | Formulario | Media | 🟡 Media | Roles + validaciones |
| 11 | `admin/usuarios/edit` | Formulario | Media | 🟡 Media | Espejo de create |
| 12 | `admin/usuarios/show` | Detalle | Media | 🟡 Media | Ficha + roles + últimos accesos |
| 13 | `admin/usuarios/historial` | Listado | Media | 🟢 Baja | Tabla de auditoría con `<details>` para JSON |
| 14 | `alertas/index` | Listado | Media | 🟡 Media | Tabla con badges semánticos, marcar leídas |
| 15 | `dashboard` | Dashboard | Alta | 🔴 Alta | KPIs, Chart.js, tabla de críticos, accesos rápidos |
| 16 | `auth/login` | Auth | Baja | 🔴 Alta | Primera impresión del sistema; usa inputs Breeze |
| 17 | `auth/register` | Auth | Baja | 🟡 Media | Usa inputs Breeze |
| 18 | `auth/forgot-password` | Auth | Baja | 🟢 Baja | Usa inputs Breeze |
| 19 | `auth/reset-password` | Auth | Baja | 🟢 Baja | Usa inputs Breeze |
| 20 | `auth/confirm-password` | Auth | Baja | 🟢 Baja | Usa inputs Breeze |
| 21 | `auth/verify-email` | Auth | Baja | 🟢 Baja | Texto simple |
| 22 | `profile/edit` | Formulario | Media | 🟡 Media | **100 % Breeze default** (gris/blanco fuera de paleta) |
| 23 | `profile/partials/update-profile-information-form` | Formulario | Media | 🟡 Media | **Breeze default** (gray/indigo) |
| 24 | `profile/partials/update-password-form` | Formulario | Media | 🟡 Media | **Breeze default** (gray/indigo) |
| 25 | `profile/partials/delete-user-form` | Formulario | Media | 🟡 Media | **Breeze default** + modal Breeze |
| 26 | `errors/403` | Error | Baja | 🟡 Media | Único error con marca. Faltan 404 / 500 |
| 27 | `layouts/app` | Layout | Alta | 🔴 Alta | Sidebar + header + footer del panel. Carga ambas fuentes |
| 28 | `layouts/guest` | Layout | Baja | 🟡 Media | Carga **solo** Space Grotesk (falta Chakra Petch) |
| 29 | `layouts/navigation` | Layout | Baja | ⚫ Nula | Breeze leftover sin ninguna referencia |
| 30 | `welcome` | — | Baja | ⚫ Nula | Sin ruta (`/` redirige a login). Incluye CSS de Tailwind v4 embebido |
| 31 | `components/alert` | Componente | Baja | 🔴 Alta | Usado en todas las vistas; ya alineado a marca |
| 32 | `components/card` | Componente | Baja | 🔴 Alta | Pieza base de casi todas las vistas |
| 33 | `components/nav-link` | Componente | Baja | 🔴 Alta | Sidebar del panel; ya alineado a marca |
| 34 | `components/session-timeout` | Componente | Alta | 🟡 Media | 202 líneas; ya alineado a marca (slate/blue) |
| 35 | `components/input-error` | Componente | Baja | 🟡 Media | `red-600`/`red-400`; aceptable pero no usa la paleta semántica exacta |
| 36 | `components/auth-session-status` | Componente | Baja | 🟢 Baja | `green-*` en vez de `emerald-*` |
| 37 | `components/input-label` | Componente | Baja | 🟡 Media | `gray-700`/`gray-300` (Breeze) |
| 38 | `components/text-input` | Componente | Baja | 🔴 Alta | `gray-*` + `indigo-*`; usado por las 6 vistas de auth |
| 39 | `components/primary-button` | Componente | Baja | 🔴 Alta | `gray-800`/`gray-200` + `indigo-*`; usado en auth |
| 40 | `components/danger-button` | Componente | Baja | 🟡 Media | `red-600` + `rounded-md`/uppercase (Breeze) |
| 41 | `components/secondary-button` | Componente | Baja | 🟡 Media | `bg-white`/`gray-800` (Breeze) |
| 42 | `components/dropdown` | Componente | Baja | 🟢 Baja | `bg-white dark:bg-gray-700` (Breeze) |
| 43 | `components/dropdown-link` | Componente | Baja | 🟢 Baja | `gray-*` (Breeze) |
| 44 | `components/responsive-nav-link` | Componente | Baja | ⚫ Nula | Breeze leftover sin referencia |
| 45 | `components/modal` | Componente | Baja | 🟢 Baja | `gray-*` (Breeze) |
| 46 | `components/application-logo` | Componente | Baja | ⚫ Nula | Logo SVG genérico de Laravel; sin referencia |

> **Nota:** `resources/views/preview/index.blade.php` **no** se cuenta en el inventario
> productivo: es el spike self-contained de validación de `frontend-design.md` en la ruta
> `/preview`, fuera del alcance del rediseño.

### Resumen por tipo

| Tipo | Cantidad |
|---|---|
| Listado | 5 (`productos/index`, `ubicaciones/index`, `usuarios/index`, `usuarios/historial`, `alertas/index`) |
| Formulario | 10 (productos 2, ubicaciones 2, usuarios 2, `profile/edit`, 3 parciales de profile) |
| Detalle | 2 (`productos/show`, `usuarios/show`) |
| Dashboard | 1 (`dashboard`) |
| Auth | 6 |
| Error | 1 (solo 403) |
| Layout | 3 (`app`, `guest`, `navigation`) |
| Componente | 17 (16 componentes + `productos/partials/atributos`) |
| Sin tipo | 1 (`welcome`, sin ruta) |
| **Total** | **46** (+ `preview/index`, fuera del alcance productivo) |

**Vistas muertas / no productivas (transversal):** `welcome`, `layouts/navigation`,
`components/responsive-nav-link`, `components/application-logo`.

---

## 2. Pain points por vista (criterio visual subjetivo)

Escala: **1 = se ve roto / fuera de marca**, **5 = se ve excelente y consistente con la marca**.

### 🔴 Se ve genérico o fuera de paleta → candidatos a rediseño

| Vista | Score | Problema |
|---|---|---|
| `profile/edit` | **1** | `bg-white dark:bg-gray-800`, `text-gray-800` — Breeze default, fuera de paleta |
| `profile/partials/update-profile-information-form` | **1** | `text-gray-900/text-gray-600` — Breeze default |
| `profile/partials/update-password-form` | **1** | `text-gray-900/text-gray-600` — Breeze default |
| `profile/partials/delete-user-form` | **1** | `gray-*` + modal Breeze |
| `components/text-input` | **2** | `gray-*` + `indigo-*`; lo heredan las 6 vistas de auth |
| `components/primary-button` | **2** | `gray-800`/`gray-200` + `indigo-*`; lo hereda auth |
| `components/input-label` | **2** | `gray-700`/`gray-300` (Breeze) |
| `components/secondary-button` | **2** | `bg-white`/`gray-800` (Breeze) |
| `components/dropdown` / `dropdown-link` | **2** | `bg-white`/`gray-*` (Breeze) |
| `components/danger-button` | **2** | `rounded-md` + uppercase, fuera del sistema de botones |
| `components/modal` | **2** | `gray-*` (Breeze) |
| `components/responsive-nav-link` | **2** | Breeze leftover |
| `components/application-logo` | **2** | Logo SVG de Laravel, no la marca |
| `auth/*` (6 vistas) | **2–3** | El layout `guest` ya es de marca, pero los inputs son Breeze (gris/indigo) |
| `admin/productos/create` | **3** | Paleta correcta, pero formulario plano: secciones sin jerarquía visual clara |
| `admin/productos/edit` | **3** | Igual que create |
| `admin/ubicaciones/create` | **3** | Formulario simple, sin identidad propia |
| `admin/ubicaciones/edit` | **3** | Igual |
| `admin/usuarios/create` | **3** | Funcional pero genérico |
| `admin/usuarios/edit` | **3** | Igual |
| `admin/productos/partials/atributos` | **3** | Campos condicionales sin tratamiento visual |
| `welcome` | **1** | Vistas muertas / restos de Breeze |

### ✅ Se ven bien → mantener (solo ajustes menores de consistencia)

| Vista | Score | Comentario |
|---|---|---|
| `admin/productos/index` | **4** | Paleta correcta, badges semánticos, chips, paginación. Bien resuelto |
| `admin/productos/show` | **4** | Cards y estructura correctas |
| `admin/ubicaciones/index` | **4** | Agrupación en dos cards, badges de tipo |
| `admin/usuarios/index` | **4** | Avatar, chips de rol por color, estado. Buen nivel |
| `admin/usuarios/show` | **4** | Ficha + roles + tabla de accesos |
| `admin/usuarios/historial` | **4** | Tabla de auditoría con badges y `<details>` |
| `alertas/index` | **4** | Badges semánticos, fila sin leer destacada |
| `dashboard` | **4** | KPIs con iconos, chart, críticos. Densidad alta pero clara |
| `errors/403` | **4** | Card centrada, escudo, CTA. Ya es de marca |
| `layouts/app` | **4** | Sidebar/header/footer bien alineados a la marca |
| `layouts/guest` | **3.5** | De marca, pero carga solo una de las dos fuentes |
| `components/card` | **4** | Base del sistema |
| `components/alert` | **4** | Colores semánticos + auto-dismiss |
| `components/nav-link` | **4** | Estados activo/inactivo bien resueltos |
| `components/session-timeout` | **4** | Modal de marca |
| `components/input-error` | **3.5** | Aceptable; unificar a la paleta semántica exacta |

**Lectura general:** el panel admin ya está, en su mayoría, **bien alineado a la marca**.
El mayor problema de "se ve genérico" está concentrado en **Breeze** (perfil, inputs de
auth, botones, dropdown, modal) y en el **bug de tipografía** que degrada *todas* las
vistas por igual. Eso respalda la estrategia del plan: no reinventar el panel, sino
**terminar de alinear lo que quedó a medias**.

---

## 3. Baseline visual — 3 vistas candidatas al prototipo showcase

Se propone **1 listado + 1 formulario + 1 detalle** (cubre los 3 patrones de UI más
frecuentes y permite extrapolar al resto del panel).

| # | Patrón | Vista propuesta | Justificación |
|---|---|---|---|
| 1 | **Listado** | `admin/productos/index` | Núcleo del negocio. Es el listado más complejo y denso (tabla, 6 filtros, chips activos, Alpine, badges de stock, paginación, estado vacío). Si el rediseño funciona acá, funciona en cualquier listado del panel |
| 2 | **Formulario** | `admin/productos/create` | Formulario más complejo (secciones, file input, campos dinámicos por categoría vía Alpine, `@error` por campo, `old()`). Define el sistema de inputs, labels y botones reutilizable en todos los formularios |
| 3 | **Detalle** | `admin/usuarios/show` | Detalle limpio y representativo (ficha con `<dl>`, badges de rol, tabla anidada de accesos). Se elige **de otro módulo** para demostrar que el patrón detalle generaliza y no es específico de productos |

**Por qué estas y no otras:**
- `productos/*` es donde el cliente pasa más tiempo y donde hay más superficie de UI.
- Incluir un detalle de **usuarios** (no de productos) evita que el showcase sea
  monocromático de un solo módulo y prueba la reutilización de componentes.
- `alertas/index` y `usuarios/index` se usan como **vistas de control** en la Fase 4:
  si al migrar el lote 3.1 (layout + componentes) esas vistas mejoran solas, confirma que
  el sistema de diseño está bien extraído.

**Qué se le muestra al cliente en el checkpoint:** 3 pares antes/después (misma vista,
mismo contenido) y una encuesta de puntuación 1–5 por par (ver §5 del plan).

---

## 4. Hallazgos

### H1 — Bug de tipografía (`font-sans` → Figtree no cargada) — **afecta a TODO**

- `tailwind.config.js` define `fontFamily.sans = ['Figtree', ...defaults]`.
- `Figtree` **no se carga en ningún layout** (los layouts cargan Space Grotesk + Chakra Petch
  desde `fonts.bunny.net`).
- `layouts/app`, `layouts/guest` y `errors/403` aplican `font-sans` en el `<body>`.
- Resultado: **el navegador cae al fallback del sistema** en todo el panel y en auth. La
  tipografía de marca nunca se ve.
- Además, `tailwind.config.js` **no tiene claves** `font-display` ni `font-body`, así que no
  hay utilidad Tailwind para aplicar Space Grotesk / Chakra Petch de forma explícita.
- Es la **Fase 0** del plan y bloquea cualquier valoración estética seria del resto.

### H2 — `layouts/guest` carga solo Space Grotesk (falta Chakra Petch)

`layouts/app` carga `space-grotesk` + `chakra-petch`; `layouts/guest` solo carga
`space-grotesk`. Inconsistencia con `frontend-design.md`, que mandata **ambas** fuentes.

### H3 — El perfil (`/profile`) es 100 % Breeze default, fuera de paleta

`profile/edit.blade.php` y sus 3 parciales usan `bg-white dark:bg-gray-800`,
`text-gray-900`, `text-gray-600` e `indigo-*`. `frontend-design.md` **prohíbe** blanco/gris
fuera de paleta. El perfil es una ruta alcanzable (`/profile`), así que hoy es la vista más
"fuera de marca" del sistema.

### H4 — Componentes Breeze fuera de paleta, heredados por auth

`primary-button`, `text-input`, `input-label`, `danger-button`, `secondary-button`,
`dropdown`, `dropdown-link` y `modal` usan `gray-*`/`indigo-*`/`bg-white`. Las 6 vistas de
auth usan `x-text-input`, `x-input-label` y `x-primary-button`, por lo que **heredan el
estilo Breeze** aunque el layout `guest` sea de marca.

### H5 — Vistas muertas (restos de Breeze)

- `welcome.blade.php`: **sin ruta** (`/` redirige a login) y con un bloque enorme de CSS de
  Tailwind v4 embebido. Peso muerto.
- `layouts/navigation.blade.php`: **sin ninguna referencia**.
- `components/responsive-nav-link.blade.php` y `components/application-logo.blade.php`:
  componentes Breeze sin referencias.
- Recomendación: eliminar en la Fase 5 (o documentar por qué se conservan).

### H6 — Páginas de error incompletas

Solo existe `errors/403.blade.php` (bien resuelto, de marca). **Faltan 404 y 500** con la
misma identidad, aunque el alcance del plan incluye "páginas de error".

### H7 — Dos convenciones de layout conviven

`profile/edit` usa `<x-app-layout>` (componente → `layouts.app`), mientras el resto del panel
usa `@extends('layouts.app')`. Además, `profile/edit` pasa un `x-slot name="header"` que
`layouts.app` **no renderiza** (el layout usa `@yield('title')`), por lo que ese encabezado
se pierde silenciosamente. Unificar en la Fase 3.

### H8 — Detalle menor: puntuaciones de color semántico

`auth-session-status` usa `green-*` (no `emerald-*`) y `input-error` usa `red-600/red-400`
directo. No rompen, pero conviene unificarlos a la paleta semántica de `frontend-design.md`
en la Fase 1/3.

---

## 5. Conclusión de la auditoría

1. El **panel admin** ya está mayormente alineado a la marca (score 3–4 en casi todas sus
   vistas). El rediseño ahí es de **pulido y consistencia**, no de reinvención.
2. El problema real y transversal es el **bug de tipografía (H1)**: hoy ninguna vista se ve
   con la tipografía que la marca define.
3. El segundo bloque de trabajo es **terminar de sacar Breeze** del perfil, auth, botones,
   dropdown y modal (H3, H4).
4. Hay **limpieza** pendiente: vistas y componentes muertos (H5) y páginas de error (H6).

Esto confirma el enfoque del plan: **Fase 0 primero** (tipografía), luego sistema de diseño,
luego prototipo y checkpoint, y recién después migración por lotes.
