# Obsidian Cyber Grid — Sistema de diseño de Pixel Store

> **Origen**: propuesta visual entregada por el cliente (mockup de login + `DESIGN.md`).
> **Este documento es la fuente de verdad** de los tokens: donde el mockup y el
> `DESIGN.md` se contradicen, acá queda registrado qué se adoptó y por qué
> (ver *Notas de reconciliación*).
> **No reemplaza** a `skills/frontend-design.md`: lo complementa durante la migración.

---

## Estado de adopción

| Etapa | Alcance | Estado |
|---|---|---|
| **Etapa 1** | Login + fundamentos (tokens, tipografía, radios) | ✅ aplicado (2026-10) |
| **Etapa 2** | Panel admin, componentes base (`x-card`, `x-input`, `x-button`), layouts | ⏳ pendiente |
| **Etapa 3** | Catálogo público | ⏳ pendiente |

**Regla de convivencia**: las vistas **nuevas** usan los tokens Obsidian. Las vistas
**existentes** del panel siguen con `slate`/`blue` (Fase 0/1) hasta que se migren en
la Etapa 2. Los tokens viejos y nuevos conviven en `tailwind.config.js`, que solo se
modifica de forma **aditiva**.

---

## Paleta

Valores tomados del `tailwind.config` del mockup (es lo que produjo el render que el
cliente aprobó). No introducir hex sueltos en las vistas: usar las clases generadas.

| Token | Hex | Clase (fondo / texto / borde) | Uso |
|---|---|---|---|
| `background` / `surface` | `#0d1322` | `bg-background`, `bg-surface` | Canvas base, panel de marca |
| `surface-container-lowest` | `#080e1d` | `bg-surface-container-lowest` | Fondo de cards (glass) |
| `surface-container-low` | `#151b2b` | `bg-surface-container-low` | Inputs |
| `surface-container` | `#191f2f` | `bg-surface-container` | Superficie elevada |
| `surface-container-high` | `#242a3a` | `bg-surface-container-high` | Cards elevadas |
| `surface-variant` | `#2f3445` | `bg-surface-variant` | Bordes suaves, celdas apagadas |
| `surface-bright` | `#33394a` | `bg-surface-bright` | Hover |
| `on-surface` | `#dde2f8` | `text-on-surface` | Texto primario |
| `on-surface-variant` | `#c3c6d7` | `text-on-surface-variant` | Texto secundario |
| `outline` | `#8d90a0` | `text-outline`, `border-outline` | Labels, metadata |
| `outline-variant` | `#434655` | `border-outline-variant` | Bordes hairline |
| `primary` | `#b4c5ff` | `text-primary` | Texto sobre `primary-container` |
| `primary-container` | `#2563eb` | `bg-primary-container` | CTA, foco |
| `on-primary-container` | `#eeefff` | `text-on-primary-container` | Texto del CTA |
| `secondary` | `#a4c9ff` | `text-secondary` | Links, hover |
| `tertiary` | `#00dbe9` | `text-tertiary` | Telemetría, live status, acento hero |
| `error` | `#ffb4ab` | `text-error` | Errores |
| `error-container` | `#93000a` | `bg-error-container` | Fondo de error |

**Compatibilidad**: `slate`, `blue`, `emerald`, `amber`, `red`, `purple` siguen
existiendo sin cambios. `primary-container` coincide con el `blue-600` (#2563eb) de
Fase 0/1, lo que hace la transición visualmente continua.

---

## Tipografía

Las tres familias se cargan desde **bunny.net** (verificado 2026-10):
Space Grotesk, Geist y JetBrains Mono. No se usa Google Fonts ni Tailwind CDN.

| Token | Familia | Tamaño / LH / Peso / Tracking | Clase |
|---|---|---|---|
| headline-xl | Space Grotesk | 48 / 56 / 700 / -0.03em | `text-headline-xl font-headline-xl` |
| headline-xl-mobile | Space Grotesk | 32 / 40 / 700 / -0.02em | `text-headline-xl-mobile` |
| headline-lg | Space Grotesk | 36 / 44 / 700 / -0.025em | `text-headline-lg` |
| headline-lg-mobile | Space Grotesk | 26 / 34 / 700 / -0.015em | `text-headline-lg-mobile` |
| headline-md | Space Grotesk | 24 / 32 / 600 / -0.015em | `text-headline-md` |
| headline-sm | Space Grotesk | 18 / 26 / 600 / -0.01em | `text-headline-sm` |
| body-lg | Geist | 16 / 24 / 400 / -0.005em | `text-body-lg` |
| body-md | Geist | 14 / 20 / 400 / 0 | `text-body-md` |
| body-sm | Geist | 12 / 18 / 400 / 0.005em | `text-body-sm` |
| label-lg | JetBrains Mono | 14 / 20 / 500 / 0.02em | `text-label-lg` |
| label-md | JetBrains Mono | 12 / 16 / 500 / 0.04em | `text-label-md` |
| label-sm | JetBrains Mono | 10 / 14 / 600 / 0.08em | `text-label-sm` |

- **Space Grotesk**: titulares y hero (nunca bloques largos).
- **Geist**: cuerpo, formularios, tablas.
- **JetBrains Mono**: labels, badges, SKUs, telemetría.

Los tokens de tamaño ya incluyen `fontWeight` en el `fontSize` del config, así que
`text-headline-xl` aplica peso y tracking sin necesidad de `font-bold`.

---

## Radios

Escala del `DESIGN.md` (sana), **no** la del mockup:

`sm 2px` · `DEFAULT 4px` · `md 6px` · `lg 8px` · `xl 12px` · `full 9999px`

- Los radios globales (`rounded-lg`, `rounded-xl`, `rounded-full`) **no se tocan**:
  siguen siendo los de Tailwind y el panel de Fase 0/1 no se ve afectado.
- Para las vistas nuevas se exponen claves namespaced equivalentes a la escala:
  `rounded-obsidian` (4px) · `rounded-obsidian-lg` (8px) · `rounded-obsidian-xl` (12px).
- `rounded-full` se mantiene en `9999px` (ver reconciliación).

---

## Componentes (referencia visual del cliente, a implementar por etapas)

| Componente | Receta |
|---|---|
| **Botón primario** | `bg-primary-container` + bevel superior blanco 20% (`inset 0 1px 0 rgba(255,255,255,.2)`) + glow azul (`0 0 24px rgba(37,99,235,.35)`); hover `bg-primary`/`/90`; `rounded-obsidian-lg` |
| **Input** | `bg-surface-container-low` + `border-outline-variant/30` + focus ring `primary-container/30`; texto `on-surface`, placeholder `outline`; ícono outlined a la izquierda |
| **Card glass** | `radial-gradient` (primary-container/15 + tertiary/8) + `backdrop-blur-2xl` + `bg-surface-container-lowest/72` + hairline `outline-variant/30` + `rounded-obsidian-xl` |
| **Chip de estado** | fondo `color/12` + texto `color` claro + borde `color/25` + `rounded-obsidian` + `text-label-md` |
| **Badge pixel** | mono uppercase, tracking amplio (`text-label-sm`, `tracking-[0.18em]`), con dot |

---

## Notas de reconciliación

1. **Canvas**: el `DESIGN.md` original decía `#070B14`; el mockup usa `#0d1322`.
   → **Adoptamos `#0d1322`** (es lo que produjo el render aprobado).
2. **`rounded-full`**: el mockup redefine `borderRadius.full: 0.75rem`, lo que
   rompería todos los chips/avatares del panel (Fase 0/1) y contradice su propio
   `DESIGN.md`. → **Mantenemos `9999px`**.
3. **Radios**: se adopta la escala del `DESIGN.md` (`sm 2 / DEFAULT 4 / md 6 / lg 8 /
   xl 12`), no los overrides del mockup.
4. **Tipografía**: la escala del mockup y la del `DESIGN.md` coinciden → sin conflicto.

## Del mockup que NO se implementa

- **WebGL shader** → reemplazado por CSS (grid + glows + animación de celdas).
  Motivo: costo/beneficio en un POS (batería, GPU integrada, equipos de tienda) y
  funcionamiento offline.
- **Tailwind CDN y `tailwind.config` inline** → `@vite` + config del proyecto.
- **Material Symbols** (~100 KB, dependencia de red) → **SVG inline**.
- **Telemetría inventada** ("Terminal Release v2.8.4", "BOU-NODE-01",
  "Latencia: 24ms", "TLS 1.3") → eliminada. No hay fuente real para publicarla.
- **Credenciales hardcodeadas** y `simulateLogin()` → eliminadas; el form es real.
