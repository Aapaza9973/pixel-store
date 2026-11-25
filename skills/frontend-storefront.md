---
name: frontend-storefront
description: Criterio de frontend para Pixel Store en su stack real (Laravel + Blade + Alpine + Tailwind 3). Traduce los principios de una skill de tienda React/Vite a este proyecto —tokens Obsidian, primitivas compartidas, tipografía, números, formularios, accesibilidad y rendimiento— sin cambiar la identidad visual ni introducir dependencias nuevas. Úsala antes de crear o rediseñar cualquier vista del panel o del catálogo, y al revisar consistencia visual.
license: Ver LICENSE.txt
---

# Frontend Pixel Store — skill adaptada

> Origen: skill `react-vite-shop-frontend` (React 19 + Vite + TS + Tailwind v4 +
> Hono en Worker). Este proyecto es **Laravel 13 + Blade + Alpine 3 + Tailwind
> 3.1**. Se conserva el **criterio**, se descarta el **stack**.

## 0. Regla que manda sobre todo

La identidad visual ya está definida y **no se negocia**: Obsidian Cyber Grid
(`docs/design/obsidian-cyber-grid.md`, tokens en `tailwind.config.js`).
"Mejorar el frontend" significa **aplicar la identidad completa donde falta**,
no inventar un lenguaje nuevo. Antes de escribir una vista, leer también
`skills/frontend-design.md`.

## 1. Stack real (no cambiar)

| Pieza | En el proyecto |
|---|---|
| Backend | Laravel 13, controladores + FormRequests |
| Vistas | Blade + componentes `<x-*>` |
| Interactividad | Alpine 3 (`x-data`, `x-show`, `x-model`) |
| Estilos | Tailwind 3.1 con `tailwind.config.js` (no CSS-first, no v4) |
| Build | Vite 7 |

**Prohibido traer**: React, Vite SPA, Hono/Worker, D1, `motion`, `lucide-react`,
`@theme` de Tailwind 4, otra paleta u otra tipografía.

## 2. Primitivas: la palanca real

El 80 % de la consistencia se gana en `resources/views/components/`. Si una
vista necesita un botón, un input o una card, **usa el componente**; si el
componente no alcanza, se extiende con props, no se duplica.

| Componente | Cuándo |
|---|---|
| `<x-card>` | Contenedor de sección con título/subtítulo opcional |
| `<x-primary-button>` | Acción principal (una por pantalla) |
| `<x-secondary-button>` | Acción secundaria, cancelar |
| `<x-danger-button>` | Acción destructiva |
| `<x-text-input>` | Todo input de texto/email/number |
| `<x-input-label>` + `<x-input-error>` | Etiqueta y error, siempre juntos |
| `<x-modal>` | Diálogos con `open-modal` / `close-modal` |
| `<x-alert>` | Mensajes flash (`success`, `error`, `warning`, `info`) |
| `<x-help-tooltip>` | Ayuda contextual corta |

Para tablas, badges y filtros no hay componente todavía: copiá el patrón de
`admin/series/index.blade.php` o `admin/auditoria/index.blade.php` (header
`label-sm uppercase`, filas `divide-outline-variant/15`, hover
`bg-primary-container/[0.04]`, cifras en `tabular-nums`).

## 3. Tipografía y números

- Escala cerrada: `headline-*` (Space Grotesk), `body-*` (Geist), `label-*`
  (JetBrains Mono, uppercase con `tracking-[0.18em]`).
- **No** `text-sm`, `text-base`, `text-lg` en vistas nuevas: usan la escala.
- Titulares con tracking cerrado; labels siempre en mayúsculas.
- **Toda cifra** (precio, cantidad, saldo, fecha) lleva `tabular-nums`.
- Unidades explícitas y formato boliviano: `Bs 1.234,56` — coma decimal, punto
  de miles. Nunca `toFixed(2)` suelto.

## 4. Formularios y validación

- Servidor: `FormRequest` con `authorize()`, `rules()`, `messages()` en español.
- Cliente: validación Alpine solo para feedback inmediato; **el servidor manda**.
- El precio y los totales **nunca** se aceptan del cliente: se recalculan en
  `VentaService`/servicios.
- Cada campo: `<x-input-label>`, control, `<x-input-error :messages>`.
- Al enviar con errores, enfocar el primer campo inválido.
- Marcar `aria-invalid` y `aria-describedby` cuando el campo tiene error.

## 5. Accesibilidad mínima (no negociable)

- Skip link "Ir al contenido" en el layout (ya existe, mantenerlo).
- `aria-label` en todo botón de icono.
- `aria-pressed` en toggles y chips.
- `aria-live="polite"` en contadores dinámicos (carrito, alertas) y avisos.
- `role="alert"` en errores de validación.
- `role="dialog"` + `aria-modal` + `aria-labelledby` en modales.
- Foco visible propio (`focus-visible:ring-2 ring-primary-container/60`).
- Respetar `prefers-reduced-motion`.

## 6. Rendimiento

- Imágenes con `width`, `height` y `aspect-ratio`; `loading="lazy"` salvo la
  imagen principal de la ficha.
- Animar solo `transform` y `opacity`; 150–300 ms en el panel.
- No construir clases dinámicamente (`bg-{{ $color }}`): Tailwind no las purga.
  Usar mapas estáticos, como ya hace `<x-alert>`.

## 7. Estado en Alpine

- Lo derivado no se persiste: se calcula con `get` (`subtotal`, `total`,
  `saldoPendiente`), como en `admin/ventas/create.blade.php`.
- `localStorage` con clave versionada (`pos.carrito.v1`) y **nunca** como única
  fuente de verdad: la venta la confirma el servidor con `operacion_id`.
- Un cambio de catálogo no debe romper un carrito viejo: los ítems faltantes se
  descartan, no se inventan.

## 8. Anti-patrones

- ❌ `slate-*`, `blue-*`, `gray-*`, `bg-white` en vistas nuevas (legacy).
- ❌ `rounded-lg`/`rounded-xl` en vez de `rounded-obsidian*`.
- ❌ `bg-blue-600` a mano en vez de `<x-primary-button>`.
- ❌ Emojis en UI, gradientes decorativos, animaciones de entrada.
- ❌ Lógica de negocio en la vista o en el controlador (va en `app/Services`).
- ❌ Duplicar un componente "solo para esta pantalla".

## 9. Checklist antes de entregar

- [ ] La vista usa `<x-card>`/botones/inputs compartidos.
- [ ] Cero clases `slate/blue/gray`.
- [ ] Escala tipográfica (`headline/body/label`), no `text-sm/base`.
- [ ] Cifras en `tabular-nums` con formato `Bs 1.234,56`.
- [ ] Estados vacío, error y carga definidos.
- [ ] `@can` en acciones sensibles y `@error` bajo cada campo.
- [ ] Foco visible y navegación por teclado probada.
- [ ] `php artisan view:cache`, `npm run build` y `php artisan test` en verde.

## 10. Mapa respecto de la skill original

| Sección original | Decisión |
|---|---|
| Stack React/Vite/Worker, `src/`, router SPA | No aplica: Blade + rutas Laravel |
| Tokens `@theme` (Tailwind v4) | Adaptado: `tailwind.config.js` + doc Obsidian |
| Estado con Context/hooks | Adaptado: Alpine + getters + claves versionadas |
| Validación isomorfa `shared/*` | Adaptado: FormRequest + Alpine; precio solo servidor |
| Componentes y variantes | Adaptado a `<x-*>` existentes |
| Tipografía y números tabulares | Tomado tal cual (escala propia) |
| Accesibilidad (§10) | Tomado: skip link, aria-live, aria-invalid, foco |
| Rendimiento (§11) | Tomado: imágenes, transform/opacity, clases estáticas |
| Pedido/persistencia (§9) | Ya implementado (venta transaccional + idempotencia) |
