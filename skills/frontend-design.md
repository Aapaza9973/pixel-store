---
name: frontend-design
description: Diseño visual para Pixel Store. Define identidad de marca (logo, icono, paleta, tipografía) y cómo aplicarla en el panel interno (denso, funcional) y el catálogo público (comercial, memorable). Referencias de producto de clase mundial. Aplicar en toda vista Blade del proyecto.
license: Ver LICENSE.txt
---

# Frontend Design — Pixel Store

> Pixel Store no es "otro admin de Laravel". Es la cara digital de una tienda de
> hardware boliviana. Cuando alguien compra una RTX 4070, la interfaz tiene que
> estar a la altura del producto. Este skill define **cómo** lograrlo sin caer en
> el look genérico de plantilla.

---

## 1. Identidad de marca — inmutable

> ⚠️ **Sistema primario (2026-10)**: Pixel Store usa el sistema **Obsidian
> Cyber Grid** (propuesta del cliente). Ver `docs/design/obsidian-cyber-grid.md`
> para tokens y `resources/views/auth/login.blade.php` para la implementación
> canónica.
>
> **Slate/blue**: sistema **legacy**, solo en el panel admin no migrado
> (`layouts/app`, `admin/*`, `errors/*`). Se migrará en bloques futuros.

Estos elementos **no se negocian** en ninguna vista. Si una decisión de diseño
los contradice, la decisión se descarta.

### 1.1 Logo e icono — SIEMPRE presentes

| Activo | Ruta real | Uso |
|---|---|---|
| **Icono (isotipo)** | `public/images/logo/pixel-icon-sm.png` | Favicon, sidebar, 403/404/500 |
| **Icono grande** | `public/images/logo/pixel-icon.png` | Variante alta resolución |
| **Logo horizontal** | `public/images/logo/pixel-logo-horizontal.png` | og:image, fondos CLAROS |
| **Logo compacto** | `public/images/logo/pixel-logo-compacto.png` | Variante compacta |
| **Favicon** | `public/images/logo/favicon.png` | Fallback |

⚠️ **Gap conocido**: NO existe logo para fondos oscuros (`pixel-logo-white.png`
no está en el repo). El logo horizontal tiene píxeles oscuros (luminancia media
75) → baja contraste sobre `slate-950`. **Tratamiento actual**: icono + wordmark
en Space Grotesk (patrón del sidebar). **Deuda**: ver `TECH_DEBT.md`.

**Reglas no negociables**:
- ✅ El **icono** aparece SIEMPRE en el `<head>` como `favicon`: `<link rel="icon" href="{{ asset('images/logo/pixel-icon-sm.png') }}">`
- ✅ El **logo completo** aparece en el header del panel y en la navbar del catálogo
- ✅ En error pages (403, 404, 500) → icono + logo, centrados
- ✅ En login/register → logo arriba del formulario
- ❌ NUNCA usar texto "Pixel Store" como sustituto del logo cuando el logo está disponible
- ❌ NUNCA deformar, rotar, recolorear ni aplicar sombras/efectos al logo
- ❌ NUNCA poner el logo sobre fondos que no sean `slate-950`, `slate-900` o blanco puro
- ✅ Tamaño mínimo del logo completo: 120px de ancho. Debajo de eso, usar solo el icono.

**Si falta algún asset**: **parar y preguntar**. No inventar un placeholder ni
usar un texto genérico "PIXEL STORE" con tipografía improvisada.

### 1.2 Paleta — Obsidian Cyber Grid (sistema primario)

Fuente de verdad: `docs/design/obsidian-cyber-grid.md`

| Rol | Clase Tailwind | Uso |
|---|---|---|
| Canvas | `bg-surface` / `bg-background` | Fondo de app |
| Superficie base | `bg-surface-container-lowest` | Fondo de cards glass |
| Superficie input | `bg-surface-container-low` | Inputs, badges |
| Superficie elevada | `bg-surface-container` | Cards secundarias |
| Superficie alta | `bg-surface-container-high` | Modales, dropdowns |
| Borde hairline | `border-outline-variant/30` | Bordes sutiles |
| Borde fuerte | `border-outline` | Separadores visibles |
| Texto primario | `text-on-surface` | Títulos, valores |
| Texto secundario | `text-on-surface-variant` | Cuerpo |
| Texto terciario | `text-outline` | Labels, metadata |
| CTA | `bg-primary-container` (`#2563eb`) | Botón principal |
| Link / hover | `text-secondary` (`#a4c9ff`) | Links, hover |
| Live / telemetría | `text-tertiary` (`#00dbe9`) | Estados activos, dots |
| Éxito | `text-emerald-400` / `bg-emerald-500/10` | Activo, confirmación |
| Advertencia | `text-amber-400` / `bg-amber-500/10` | Stock bajo, avisos |
| Error | `text-error` / `border-error/60` | Peligro, error, focus error |

**Reglas**:
- ❌ No introducir colores fuera de esta paleta ni de `obsidian-cyber-grid.md`
- ❌ No usar `bg-white`, `bg-gray-*`, `text-gray-*`, `indigo-*`, `dark:*`
- ✅ Los colores **comunican** (emerald=éxito, amber=aviso, error=fallo)

### Paleta legacy (no usar en vistas nuevas)

`slate-950`, `slate-900`, `slate-800`, `blue-600`, `blue-400` — solo en
vistas del panel admin que aún no se migraron. Al **editar** una vista
existente: mantener la paleta legacy hasta que se migre en bloque. Al
**crear** una vista nueva: usar Obsidian.

### 1.3 Tipografía — Space Grotesk + Geist + JetBrains Mono

Fuente: `docs/design/obsidian-cyber-grid.md` §Typography

| Rol | Clase Tailwind | Familia | Uso |
|---|---|---|---|
| Headlines | `font-headline-xl` / `font-headline-lg` / `font-headline-md` / `font-headline-sm` | Space Grotesk | H1, H2, títulos de card |
| Body | `font-body-lg` / `font-body-md` / `font-body-sm` | Geist | Cuerpo, formularios, tablas |
| Labels | `font-label-lg` / `font-label-md` / `font-label-sm` | JetBrains Mono | Metadata, badges, mono uppercase |

**Escala** (definida en `tailwind.config.js`):
- `text-headline-xl` 48/56/700 (o `text-headline-xl-mobile` 32/40/700 en <lg)
- `text-headline-lg` 36/44/700
- `text-headline-md` 24/32/600
- `text-headline-sm` 18/26/600
- `text-body-lg` 16/24/400 · `text-body-md` 14/20/400 · `text-body-sm` 12/18/400
- `text-label-lg` 14/20/500 · `text-label-md` 12/16/500 · `text-label-sm` 10/14/600

**Reglas**:
- ✅ Cada texto declara **familia + tamaño**: `font-headline-md text-headline-md`
- ✅ Labels en `label-*` van uppercase con `tracking-[0.18em]`
- ✅ Códigos/SKUs en `font-label-md` o `font-mono`
- ❌ No mezclar más de 2 familias en un bloque
- ❌ No usar `text-sm`, `text-base`… en vistas Obsidian (usar la escala)

**Legacy**: Chakra Petch solo en panel admin no migrado. Al migrar, reemplazar
por Geist (body) o Space Grotesk (títulos).

### 1.4 Border radius — coherencia Obsidian

| Clase | Píxeles | Uso |
|---|---|---|
| `rounded-obsidian` | 4px | Botones, inputs, badges chicos |
| `rounded-obsidian-lg` | 8px | Cards medianas, dropdowns |
| `rounded-obsidian-xl` | 12px | Cards grandes, modales, containers |
| `rounded-sm` | 2px | Celdas de pixel bar, detalles |
| `rounded-full` | 9999px | Avatares, dots de estado |

**Reglas**:
- ❌ No usar `rounded-lg`, `rounded-xl` (radius legacy del panel)
- ✅ Elemento hijo mantiene proporción con el padre: card `xl` → input `lg`
- ✅ Contenedores glass con `rounded-obsidian-xl`

---

## 2. Los dos contextos — no son lo mismo

Antes de empezar cualquier vista, identificar el contexto. **Las reglas cambian.**

| Contexto | Rutas | Objetivo | Densidad | Riesgo estético |
|---|---|---|---|---|
| **Panel interno** | `/admin/*`, `/alertas`, `/dashboard` | Productividad, velocidad de lectura | Alta | ❌ Nulo |
| **Catálogo público** | `/`, `/catalogo/*`, `/producto/*`, `/carrito/*` | Descubrimiento, deseo, compra | Baja-media | ✅ Justificado |

**Regla de oro**: el panel interno **no es un lienzo de experimentación**. Ahí la
mejor UX es la que desaparece. Los riesgos estéticos van al catálogo.

---

## 3. Inspiración — qué tomar de quién

No copiar pixel por pixel, sino **tomar prestados principios**. Referencias por
contexto:

### 3.1 Panel interno — referencias de producto

| Producto | Qué tomar | Qué NO copiar |
|---|---|---|
| **Linear** | Densidad, jerarquía por peso tipográfico, atajos de teclado visibles, tablas impecables, uso de `font-mono` para datos | Su paleta violeta; su minimalismo excesivo para un POS |
| **Stripe Dashboard** | Números primero, formato consistente de cifras, estados con badges claros, tablas que respiran sin desperdiciar espacio | Su densidad extrema en todos los niveles |
| **Vercel Dashboard** | Dark mode bien ejecutado, acento único, dashboard de métricas con foco en el dato | Su "casi nada de color" |
| **GitHub** | Estados con color semántico, labels/badges, coherencia entre modos | Su densidad de información en settings |
| **Notion** | Jerarquía visual en headings, espaciado en formularios, tablas legibles | Su "aire" excesivo para un admin |

### 3.2 Catálogo público — referencias de producto

| Producto | Qué tomar | Qué NO copiar |
|---|---|---|
| **Apple** | Producto como héroe, whitespace generoso, tipografía dramática, una idea por sección | Su minimalismo extremo (no aplica a una tienda con 500 SKUs) |
| **Stripe (marketing)** | Refinamiento tipográfico, gradientes sutiles si son justificados, motion con restraint | Los gradientes "arcoíris" que no aportan |
| **Linear (marketing)** | Motion orquestado, screenshots de producto como prueba, secciones densas pero respiradas | Su estética exacta (todos los SaaS se ven igual) |
| **Raycast** | Dark + acentos de color bien usados, tipografía con carácter | Su foco en keyboard-first |
| **Arc Browser** | Personalidad visual, momentos memorables, microinteracciones con propósito | Su juguetón extremo |
| **Figma** | Precisión, claridad, uso inteligente del color para categorizar | Su escala (es un producto de diseño para diseñadores) |

### 3.3 Anti-referencias — looks que NO queremos

Estos son **defaults de IA** o **plantillas genéricas**. Caer en ellos es fallar:

- ❌ Crema `#F4F1EA` + serif de alto contraste + acento terracota
- ❌ Fondo casi negro + un solo acento verde ácido / vermilion
- ❌ Broadsheet: reglas hairline, cero border-radius, columnas densas tipo periódico
- ❌ AdminLTE / Tabler / plantillas Bootstrap de dashboard
- ❌ Hero "título + subtítulo + dos botones + mockup de laptop"
- ❌ Cards de "Features" con iconos Heroicons de línea fina en 3 columnas
- ❌ Numeración `01 / 02 / 03` cuando no hay secuencia real

---

## 4. Principios de diseño

### 4.1 El sujeto manda: hardware real

Pixel Store vende productos físicos con **especificaciones medibles**: SKUs,
sockets, TDP, benchmarks, compatibilidad. Ese es el material con el que se diseña.

**Aplicación concreta**:

- ✅ Los **números son ciudadanos de primera clase**. Alineación tabular, unidades
  explícitas (`Bs 2.500`, `12 u.`, `3 años`), formato consistente en todo el sistema.
- ✅ Los **SKUs y códigos** van en `font-mono` con `tracking-tight`, no en Chakra Petch.
- ✅ Los **estados del stock** (disponible / bajo / agotado) usan color semántico con
  badge, no texto suelto.
- ✅ En el catálogo, la ficha de producto trata la **tabla de especificaciones** como
  documento técnico de honor: densa, escaneable, honesta.
- ❌ No usar fotos de stock genéricas de "personas felices con laptops".
- ❌ No usar emojis en headings ni textos de UI.

### 4.2 Estructura que informa

Los recursos visuales deben **codificar verdad**, no decorar.

| ✅ Bueno | ❌ Malo |
|---|---|
| Badge de estado con color semántico | Punto de color decorativo sin leyenda |
| Numeración en un flujo POS (paso 1 → 2 → 3) | `01 / 02 / 03` en un listado de productos |
| Divider entre secciones semánticas (Categoría / Precio / Disponibilidad) | Divider cada 3 elementos "para respirar" |
| Eyebrow `Rol:` antes de un badge | Eyebrow decorativo `✨ DESTACADO` |
| Ícono que ayuda a encontrar el campo (`🔍` en buscador) | Ícono decorativo al lado de cada campo |

### 4.3 Tipografía como carácter

En el panel interno, la personalidad viene de la **precisión**: pesos coherentes,
escala clara, spacing deliberado. En el catálogo, viene del **contraste**: pesos
extremos bien usados (400 vs 700), jerarquía dramática, tracking ajustado.

**Regla de contraste**: en cada vista, el elemento tipográfico más importante debe
ser **obvio**. Si todo pesa igual, nada importa.

### 4.4 Motion con intención

**Panel interno**: mínimo.
- ✅ `transition` (150ms) en hover/focus de botones, links, filas
- ✅ Feedback inmediato en acciones (spinner, disabled state)
- ❌ Animaciones decorativas, entradas escalonadas, parallax

**Catálogo público**: orquestado.
- ✅ Una coreografía: hero entra en secuencia, luego el resto estático
- ✅ Scroll-triggered reveal **en bloques**, no en cada elemento
- ✅ Hover microinteracciones en cards de producto
- ❌ No animar todo. Es la marca #1 de diseño generado por IA.
- ✅ Respetar `prefers-reduced-motion` siempre

### 4.5 El hero como tesis

En el catálogo público, el hero **no es decoración**: es la tesis de la página.
Abre con lo más característico del sujeto, en la forma que mejor le sirva:

- **Datos en vivo** ("148 componentes en stock · actualizado hace 2 min")
- **Ficha técnica** del producto estrella (RTX 4070: GPU, memoria, TDP)
- **Comparador** (RTX 4060 vs 4070 con specs reales lado a lado)
- **Demo interactivo** (armado de PC paso a paso)

**Anti-patrón explícito**: título + subtítulo + 2 botones + mockup de laptop
sobre gradiente azul. Ese combo ya no comunica nada.

**Regla**: si el hero se puede reemplazar por cualquier otra tienda de
tecnología sin que se note, no es una tesis. Es relleno.

### 4.6 El elemento signature

Cada página pública tiene **UN** elemento memorable. Uno solo. Ese elemento:

1. **Encarna algo verdadero del sujeto** (no decora)
2. **Se repite en toda la página** de forma coherente
3. **No se puede copiar** a otra tienda sin perder sentido

Ejemplos aplicados a Pixel Store:
- **Barra de señal pixel**: 12 celdas que codifican stock o specs. Color
  semántico por estado (azul normal, ámbar bajo, apagado agotado).
- **Spec readout**: fila de datos monospace con números tabulares.
- **Grid pixelado de fondo** (sutil, marca no decoración).

**Regla Chanel**: antes de entregar, quitar un accesorio. Si dudás entre dos
signatures, elegí uno y borrá el otro.

### 4.7 Complejidad calibrada

- **Minimalismo** requiere **precisión**: 1px de diferencia importa. Espaciado sin
  excepción, jerarquía tipográfica perfecta.
- **Maximalismo** requiere **detalle**: si vas a usar un gradiente, que sea el
  mejor gradiente posible. Si vas a animar, que sea la mejor animación.
- Nunca a medias: elegir dirección y ejecutarla con rigor.

---

## 5. Componentes y patrones

### 5.1 Componentes existentes — reutilizar primero

Antes de crear cualquier vista, verificar qué hay en `resources/views/components/`:

- `<x-card>` — contenedor estándar (`bg-slate-900`, `border-slate-800`, `rounded-xl`) — **legacy** hasta la migración de la Etapa 2
- `<x-alert>` — mensajes flash — **legacy** hasta la Etapa 2
- `<x-nav-link>` — navegación del sidebar — **legacy** hasta la Etapa 2

En vistas **nuevas** Obsidian, preferir las superficies de §5.2 en lugar de `<x-card>`
(que todavía renderiza tokens legacy).

Si algo similar existe, **extenderlo** (props/variantes), no duplicarlo.

### 5.2 Anatomía de una vista del panel (Obsidian)

Referencia: `resources/views/auth/login.blade.php` para clases concretas.

```blade
@extends('layouts.app')

@section('title', 'Título')
@section('subtitle', 'Descripción')

@section('content')
<div class="space-y-6">

    {{-- Header de sección --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                Sección
            </p>
            <h1 class="mt-1 font-headline-md text-headline-md text-on-surface">
                Título
            </h1>
        </div>
        @can('crear X')
            <a href="..."
               class="inline-flex items-center gap-2 rounded-obsidian-lg
                      bg-primary-container px-4 py-2.5
                      text-body-md font-medium text-on-primary-container
                      shadow-[inset_0_1px_0_rgba(255,255,255,0.2),0_0_20px_rgba(37,99,235,0.35)]
                      transition hover:bg-primary-container/90
                      focus:outline-none focus-visible:ring-2
                      focus-visible:ring-primary-container/60">
                Nuevo
            </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="rounded-obsidian-lg border border-outline-variant/30
                bg-surface-container-low/40 p-4">
        <form method="GET">...</form>
    </div>

    {{-- Tabla --}}
    <div class="rounded-obsidian-xl border border-outline-variant/30
                bg-surface-container-lowest/60 backdrop-blur-xl overflow-hidden">
        <table class="min-w-full text-body-md">...</table>
    </div>
</div>
@endsection
```

### 5.3 Tablas Obsidian

- Header sticky: `bg-surface-container-low` + `text-label-sm uppercase tracking-[0.18em] text-outline`
- Filas separadas por `border-b border-outline-variant/15`
- Hover de fila: `hover:bg-primary-container/[0.04]`
- Celdas: `px-4 py-3.5`
- Números: `tabular-nums font-label-md`
- Acciones: links `text-secondary hover:text-on-surface`

### 5.4 Badges Obsidian

```blade
{{-- Estado --}}
<span class="inline-flex items-center gap-1.5 rounded-obsidian px-2 py-1
             font-label-sm text-label-sm uppercase tracking-[0.14em]
             bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
    Activo
</span>

{{-- Neutral --}}
<span class="... bg-surface-container-low text-on-surface-variant
             border border-outline-variant/30">...</span>
```

### 5.5 Anatomía del catálogo público

Estructura mínima de una vista pública (home, listado, ficha de producto):

┌──────────────────────────────────────────────┐
│ HEADER: icono + wordmark · nav · CTA         │
├──────────────────────────────────────────────┤
│ HERO: tesis + elemento signature             │
│  (asimetría 2-col o composición no genérica) │
├──────────────────────────────────────────────┤
│ SECCIÓN PRODUCTOS: grid 2/3 cols             │
│  cards con specs + precio + stock            │
├──────────────────────────────────────────────┤
│ FOOTER: icono + marca + ubicación + ©        │
└──────────────────────────────────────────────┘

**No extiende `layouts.app`** (ese layout asume usuario autenticado con sidebar).
Cuando el catálogo sea real, crear `layouts.public`.

**Componentes públicos futuros** (no crear todavía):
- `<x-product-card>` — datos de producto
- `<x-pixel-bar>` — barra de señal signature
- `<x-spec-table>` — tabla de especificaciones densa

### 5.6 Patrones del dashboard (Obsidian)

**Layout**: grid de 12 columnas con `gap-6` (24px).
- Fila de KPIs: `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4`
- Sección principal: `grid grid-cols-1 lg:grid-cols-3 gap-6`
  (2/3 para gráfico o tabla principal · 1/3 para lista secundaria)

**Card de KPI** (patrón canónico):

```blade
<article class="group relative flex flex-col gap-3 overflow-hidden
                rounded-obsidian-xl border border-outline-variant/30
                bg-surface-container-lowest/60 p-5 backdrop-blur-xl
                transition-all duration-300
                hover:border-primary-container/40 hover:bg-surface-container-lowest/80">

    {{-- Header: label + ícono --}}
    <div class="flex items-start justify-between">
        <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
            Ventas del día
        </p>
        <div class="flex h-9 w-9 items-center justify-center rounded-obsidian
                    border border-outline-variant/30 bg-surface-container-low/60
                    text-tertiary transition-colors group-hover:text-primary">
            <svg class="h-4 w-4" ...>...</svg>
        </div>
    </div>

    {{-- Valor principal --}}
    <div class="flex items-baseline gap-2">
        <p class="font-headline-lg text-headline-lg text-on-surface tabular-nums">
            Bs 4.280
        </p>
        <span class="font-label-md text-label-md text-emerald-400">+12,4 %</span>
    </div>

    {{-- Subtexto --}}
    <p class="font-body-sm text-body-sm text-on-surface-variant/80">
        vs. ayer · Bs 3.810
    </p>
</article>
```

**Reglas del KPI**:
- Valor en `text-headline-lg` + `tabular-nums`
- Delta con color semántico (emerald positivo, error negativo)
- Ícono en cuadro con borde (nunca suelto)
- Hover: borde `primary-container/40` (no lift)
- Nunca mostrar gráficos en un KPI — solo dato + delta

**Sección de gráfico principal**:

```blade
<div class="rounded-obsidian-xl border border-outline-variant/30
            bg-surface-container-lowest/60 p-6 backdrop-blur-xl">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <p class="font-label-sm text-label-sm uppercase tracking-[0.18em] text-outline">
                Tendencia
            </p>
            <h3 class="mt-1 font-headline-sm text-headline-sm text-on-surface">
                Ventas de los últimos 30 días
            </h3>
        </div>
        <select class="rounded-obsidian bg-surface-container-low
                       border-outline-variant/30 px-3 py-1.5
                       font-label-md text-label-md text-on-surface-variant
                       focus:border-primary-container focus:ring-2
                       focus:ring-primary-container/25">
            <option>30 días</option>
        </select>
    </div>

    <div class="h-64">
        {{-- Chart library o SVG inline --}}
    </div>
</div>
```

**Lista de actividad reciente** (1/3 de la columna):
- Filas con `divide-y divide-outline-variant/15`
- Cada fila: ícono (cuadro 8x8) + texto principal + timestamp en `label-sm text-outline`
- Máximo 5-7 filas visibles

**Reglas del dashboard**:
- **Jerarquía visual**: KPIs arriba, gráfico central, actividad secundaria
- **Espaciado**: `space-y-6` entre secciones, `gap-4` entre KPIs
- **Superficies**: siempre `bg-surface-container-lowest/60` + `backdrop-blur-xl`
  (glass consistente con el login)
- **Sin gradientes decorativos** (solo los del login en el aside)
- **Sin animaciones** más allá de `hover` y `transition` (el dashboard es
  dato, no espectáculo). Excepción: `animate-ping` para dots "live"
- **Números primero**: el valor siempre en `headline-*`, la unidad en
  `body-sm text-outline`

