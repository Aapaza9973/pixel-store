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

Estos elementos **no se negocian** en ninguna vista. Si una decisión de diseño
los contradice, la decisión se descarta.

### 1.1 Logo e icono — SIEMPRE presentes

| Activo | Ruta | Uso |
|---|---|---|
| **Icono** (isotipo) | `public/images/logo/pixel-icon-sm.png` | Favicon, avatar del sidebar colapsado, 403/404, emails |
| **Logo completo** (isotipo + wordmark) | `public/images/logo/pixel-logo.png` *(verificar ruta exacta)* | Header del panel, navbar del catálogo, login, footer |
| **Logo blanco** (para fondos oscuros) | `public/images/logo/pixel-logo-white.png` *(si existe)* | Preferente dado el dark-first del proyecto |

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

### 1.2 Paleta — dark-first, acento azul

El proyecto es **dark-first**. No hay versión light. Los colores están alineados
con Tailwind y existen como clases; no introducir hex sueltos.

| Rol | Clase Tailwind | Hex (referencia) | Uso |
|---|---|---|---|
| Fondo app | `bg-slate-950` | `#020617` | `<body>`, fondo global |
| Superficie | `bg-slate-900` | `#0f172a` | `<x-card>`, sidebar, header |
| Elevado | `bg-slate-800` | `#1e293b` | Inputs, hover, tabla header |
| Borde | `border-slate-800` / `border-slate-700` | — | Separadores, inputs |
| Texto primario | `text-white` | `#ffffff` | Títulos, valores numéricos |
| Texto secundario | `text-slate-300` | `#cbd5e1` | Cuerpo, descripciones |
| Texto terciario | `text-slate-400` / `text-slate-500` | — | Labels, captions |
| **Acento primario** | `bg-blue-600` + `hover:bg-blue-700` | `#2563eb` | Botón principal, CTA |
| **Acento informativo** | `text-blue-400` / `text-blue-500` | `#60a5fa` | Links, badges, focus ring |
| Éxito | `text-emerald-400` / `bg-emerald-500/10` | `#10b981` | Estado activo, confirmación |
| Advertencia | `text-amber-400` / `bg-amber-500/10` | `#f59e0b` | Stock bajo, avisos |
| Peligro | `text-red-400` / `bg-red-500/10` | `#ef4444` | Eliminar, inactivo, error |
| Especial (rol) | `text-purple-400` / `bg-purple-500/10` | `#a855f7` | Distinguir roles sin alarma |

**Reglas**:
- ❌ No introducir colores fuera de esta paleta. Si hace falta, **parar y preguntar**.
- ❌ No usar gradientes decorativos en el panel interno. En el catálogo público se
  permiten **solo si son sutiles** (azul → transparente, no arcoíris).
- ❌ No usar `bg-white`, `bg-gray-*` (usar `slate-*`), `bg-black` puro.
- ✅ El color **comunica**: verde = activo, rojo = peligro/inactivo, ámbar = atención.
  No usar colores semánticos como decoración.
- ✅ El azul es el único color "de marca" más allá de los semánticos.

### 1.3 Tipografía — Space Grotesk + Chakra Petch

Ambas están cargadas en el layout vía `fonts.bunny.net`. **No agregar familias nuevas.**

| Rol | Familia | Peso | Uso |
|---|---|---|---|
| **Display** | `Space Grotesk` | 500–700 | `<h1>`, `<h2>`, títulos de card, hero del catálogo |
| **Body** | `Chakra Petch` | 400–500 | Cuerpo, formularios, tablas, botones, navegación |
| **Utility** | `font-mono` (system) | 400 | SKUs, IDs, códigos técnicos |

**Reglas**:
- ✅ **Space Grotesk**: usar con moderación. Su carácter angular y geométrico destaca
  cuando es escaso. Un `<h1>` grande con Space Grotesk 700 en `text-white` ya es
  statement suficiente.
- ✅ **Chakra Petch**: su angularidad funciona perfecto para datos densos
  (tablas, fórmulas, specs). Es una fuente técnica, no una fuente de lectura larga.
  En bloques de texto >4 líneas, bajar peso a 400 y aumentar `leading-relaxed`.
- ✅ **Escala tipográfica** (Tailwind): `text-xs` (11–12), `text-sm` (13–14), `text-base`
  (16), `text-lg` (18), `text-xl` (20), `text-2xl` (24), `text-3xl` (30), `text-4xl` (36),
  `text-5xl` (48). No usar tamaños intermedios ni arbitrarios (`text-[17px]`).
- ❌ No mezclar más de 2 pesos en un mismo bloque.
- ❌ No usar `font-black` (900) — el máximo es 700.

### 1.4 Border radius — coherente

- `rounded-lg` (8px): inputs, botones, badges pequeños
- `rounded-xl` (12px): cards, modales
- `rounded-full`: avatares, avatares de iniciales, chips de rol
- ❌ No mezclar radios distintos en un mismo contexto. Si una card es `rounded-xl`,
  todos sus hijos respetan la coherencia (inputs dentro de la card siguen siendo
  `rounded-lg`, no `rounded-2xl`).

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

### 4.5 Complejidad calibrada

- **Minimalismo** requiere **precisión**: 1px de diferencia importa. Espaciado sin
  excepción, jerarquía tipográfica perfecta.
- **Maximalismo** requiere **detalle**: si vas a usar un gradiente, que sea el
  mejor gradiente posible. Si vas a animar, que sea la mejor animación.
- Nunca a medias: elegir dirección y ejecutarla con rigor.

---

## 5. Componentes y patrones

### 5.1 Componentes existentes — reutilizar primero

Antes de crear cualquier vista, verificar qué hay en `resources/views/components/`:

- `<x-card>` — contenedor estándar (`bg-slate-900`, `border-slate-800`, `rounded-xl`)
- `<x-alert>` — mensajes flash
- `<x-nav-link>` — navegación del sidebar

Si algo similar existe, **extenderlo** (props/variantes), no duplicarlo.

### 5.2 Anatomía de una vista de panel

```blade
@extends('layouts.app')

@section('title', 'Usuarios')
@section('subtitle', 'Gestión del personal con acceso al sistema')

@section('content')
<div class="space-y-6">
    {{-- 1. Header de sección con acción primaria --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-400">
            Total: <span class="text-white font-semibold">{{ $items->total() }}</span>
        </p>
        @can('crear usuarios')
            <a href="..." class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4">...</svg>
                Nuevo usuario
            </a>
        @endcan
    </div>

    {{-- 2. Filtros/búsqueda (dentro de x-card) --}}
    <x-card>
        <form method="GET">...</form>
    </x-card>

    {{-- 3. Tabla o listado principal --}}
    <x-card>
        <table class="min-w-full divide-y divide-slate-800 text-sm">...</table>
        @if ($items->hasPages())
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
    </x-card>
</div>
@endsection

