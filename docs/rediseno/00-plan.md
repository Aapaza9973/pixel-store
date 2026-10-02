# Plan de rediseño frontend — Pixel Store

> **Estado:** propuesta, pendiente de aprobación del cliente.
> **Turno:** planificación (no se tocó ninguna vista, layout, componente, CSS ni `tailwind.config.js`).
> **Documento hermano:** `docs/rediseno/01-auditoria.md` (inventario real de vistas).

---

## 1. Contexto y driver

El cliente pidió que el sistema **"se vea mejor"**. No reportó ningún dolor funcional
(la funcionalidad está completa y con 146 tests en verde) y **no definió un criterio de
éxito medible**.

Consecuencia directa: **el riesgo de este proyecto es subjetivo, no técnico.**
No se puede "terminar" un pedido estético sin un punto de acuerdo explícito con quien
lo pidió. Si se migra todo a ciegas y el cliente no queda convencido, se habrán gastado
~25 h en algo que se rechaza.

La identidad de marca ya existe y está documentada en `skills/frontend-design.md`
(paleta dark-first, Space Grotesk + Chakra Petch, logo, tono). El problema no es *falta
de identidad*, es que:

1. Varias vistas todavía usan **los valores por defecto de Breeze** (grises/blancos
   fuera de paleta), y
2. Hay un **bug de tipografía que afecta a TODO el panel** (ver Hallazgos en la auditoría).

Por eso el rediseño es, en gran parte, **alinear lo existente con la marca** más que
inventar una estética nueva.

---

## 2. Alcance

### ✅ Dentro del alcance
- **Panel interno (admin):** dashboard, productos, ubicaciones, usuarios, alertas.
- **Autenticación:** login, registro, recuperar/confirmar contraseña, verificar correo.
- **Perfil de usuario** (`/profile`, hoy 100 % Breeze default).
- **Páginas de error** (hoy solo existe `403`).
- **Layouts y componentes reutilizables** (`layouts/app`, `layouts/guest`, `x-card`,
  `x-alert`, botones e inputs Breeze que estén fuera de paleta).
- **Bug de tipografía** (`font-sans` → Figtree no cargada). Es Fase 0 y bloquea todo lo demás.

### ❌ Fuera del alcance
- **Catálogo público / e-commerce.** No existe todavía: es Sprint 3+ y deberá diseñarse
  con `frontend-design.md` desde cero (contexto público = riesgo estético justificado).
- **Lógica de negocio, backend, base de datos, rutas, tests funcionales.** Este plan no
  cambia comportamiento, solo presentación.
- **Refactors funcionales** que aparezcan durante el rediseño → van a `TECH_DEBT`, no se hacen ahora.

---

## 3. Estrategia: demostrar antes de migrar

No se migra por fe. Se construye primero un **prototipo de 3 vistas showcase**
(1 listado + 1 formulario + 1 detalle), se muestra al cliente en un **checkpoint
obligatorio**, y **solo si aprueba** se invierte en la migración completa por lotes.

```
Fase 0 → Fase 1 → Fase 2 → 🔴 CHECKPOINT CLIENTE 🔴 → Fase 3 → Fase 4 → Fase 5
(fix)    (audit)   (proto)   (aprobar / ajustar / parar)  (migrar)  (QA)    (cierre)
```

Ventaja: si el cliente no aprueba, el costo hundido son ~10 h (Plan A reducido), no 25 h.

---

## 4. Fases

| Fase | Nombre | Entregable | Estimación |
|---|---|---|---|
| **0** | Fix bug de fuentes | `font-sans` apunta a la tipografía real (Space Grotesk/Chakra Petch) en layout y config; verificación visual | **1 h** |
| **1** | Auditoría + sistema de diseño | Inventario cerrado (ya iniciado en `01-auditoria.md`) + tokens/variantes de componentes documentados | **4–5 h** |
| **2** | Prototipo 3 vistas showcase | 1 listado + 1 formulario + 1 detalle rediseñados en una ruta de preview aislada (sin tocar las reales) | **4–5 h** |
| 🔴 | **CHECKPOINT CLIENTE** | Presentación antes/después + encuesta de puntuación (ver §5) | — |
| **3** | Migración por lotes | Lotes 3.1–3.5 (abajo) | **8–10 h** |
| **4** | QA visual | Consistencia, responsive, accesibilidad, estados vacíos/error | **3–4 h** |
| **5** | Cierre | Actualizar `frontend-design.md`, limpiar vistas muertas de Breeze, `pint --dirty` | **1–2 h** |
| | **TOTAL** | | **~21–27 h (≈25 h)** |

### Detalle de la Fase 3 — lotes de migración

| Lote | Alcance | Estimación |
|---|---|---|
| **3.1** | Layouts (`app`, `guest`) + componentes base (`x-card`, `x-alert`, botones/inputs Breeze) | 2 h |
| **3.2** | Dashboard + Alertas | 1.5–2 h |
| **3.3** | Productos (index, create, edit, show, partial atributos) | 2.5–3 h |
| **3.4** | Usuarios (index, create, edit, show, historial) + Ubicaciones | 2–2.5 h |
| **3.5** | Auth (6 vistas) + Perfil + Error 403 | 1.5–2 h |

> Los números del lote 3.x son tentativos: se recalibrarán con datos reales después de la Fase 2.

---

## 5. Criterios de validación (checkpoint cliente)

El checkpoint de la Fase 2 es el punto de no-retorno. Para que no sea "me gusta / no me
gusta", se validan **3 pares antes/después** (una captura del estado actual y una del
prototipo, misma vista y mismo contenido).

El cliente puntúa **cada par de 1 a 5** (1 = "prefería el anterior", 5 = "mucho mejor").

- **Umbral de aprobación: promedio ≥ 4.**
- Si el promedio es **≥ 4** → se aprueba la migración completa (Fase 3).
- Si el promedio es **< 4** → no se migra; se aplica el **Plan A reducido** (§8) y se
  recogen los comentarios por par para un segundo prototipo.

Criterio adicional: el cliente confirma explícitamente que **no se degradó ninguna
función** (los tests siguen verdes en cada lote; ver §7).

---

## 6. Riesgos y mitigaciones

| # | Riesgo | Prob. | Impacto | Mitigación |
|---|---|---|---|---|
| R1 | El cliente no queda conforme con el resultado estético | Alta | Alto | Prototipo + checkpoint antes de migrar (Fase 2). Bajo costo hundido (10 h) |
| R2 | "Se ve mejor" es subjetivo y no hay criterio de éxito | Alta | Alto | Puntuación 1–5 por par antes/después, umbral ≥ 4 (§5) |
| R3 | Scope creep: surgen vistas/cambios fuera del plan | Media | Medio | Regla dura: todo lo fuera del plan → `TECH_DEBT`, no se hace ahora (§7) |
| R4 | El rediseño rompe una vista o un test | Media | Alto | Un lote por sesión, `php artisan test` verde entre lotes, rollback del lote ante regresión |
| R5 | Se "arregla" el catálogo público que aún no existe | Baja | Medio | Catálogo público explícitamente fuera de alcance (Sprint 3+) |
| R6 | Inconsistencia entre lotes (cada uno "a su manera") | Media | Medio | Sistema de diseño + tokens cerrados en Fase 1; QA visual en Fase 4 |
| R7 | El bug de fuentes queda "a medias" y contamina todo el resto | Media | Alto | Es la Fase 0, con verificación propia antes de arrancar Fase 1 |

---

## 7. Reglas de ejecución

1. **Un lote por sesión.** No se mezclan lotes en un mismo turno.
2. **Tests verdes entre lotes.** `php artisan test` debe pasar antes de dar un lote por cerrado.
3. **Rollback del lote ante regresión.** Si un lote rompe algo, se revierte el lote entero
   antes de seguir; no se "parchea hacia adelante".
4. **No scope creep.** Cualquier cosa que aparezca fuera del plan → anotar en `TECH_DEBT`,
   no se implementa en este rediseño.
5. **No se toca lógica de negocio.** Solo presentación (vistas, componentes, layouts, config de fuentes).
6. **Un commit por lote** (previa autorización), con mensaje claro.
7. **Cada lote se verifica visualmente** antes de pasar al siguiente.

---

## 8. Plan A reducido (si el cliente no aprueba las ~25 h)

Alcance: **solo Fases 0–2, 10 h.** No se migra ninguna vista real.

1. Fix del bug de fuentes (1 h).
2. Sistema de diseño mínimo (2 h).
3. Prototipo de las 3 vistas showcase (5 h).
4. Presentación + decisión (2 h).

Con esto el cliente ve el "antes/después" y decide con info real si quiere invertir en la
migración completa. Si dice no, el proyecto se queda con el fix de fuentes (que ya es una
mejora real) y sin haber quemado las 15 h restantes.

---

## 9. Definition of Done (del rediseño completo)

- [ ] Bug de `font-sans` corregido y verificado en panel + auth.
- [ ] Todas las vistas del alcance usan solo la paleta de `frontend-design.md`
      (sin `bg-white`, `gray-*`, `indigo-*` de Breeze).
- [ ] Layouts y componentes base unificados (lote 3.1 cerrado).
- [ ] Todos los lotes 3.1–3.5 cerrados con tests verdes.
- [ ] QA visual: consistencia, responsive y estados vacíos/error revisados.
- [ ] Par de checklist: `php artisan test` verde y `./vendor/bin/pint --dirty` en PASS.
- [ ] `frontend-design.md` actualizado con lo aprendido.
- [ ] Vistas muertas de Breeze evaluadas (`welcome`, `layouts/navigation`) → eliminar o dejar
      documentado por qué se conservan.
- [ ] Checkpoint cliente con promedio ≥ 4 registrado.

---

## 10. Formatos de ejecución

El plan se puede ejecutar de tres maneras equivalentes:

| Formato | Cadencia | Duración total aprox. |
|---|---|---|
| **Full-time** | Sesiones largas seguidas | **3–4 días** |
| **Part-time** | 1–2 h por día | **2–3 semanas** |
| **Por lotes** | Un lote por vez, pausando entre lotes | Se acuerda por lote |

El checkpoint de la Fase 2 marca el final del compromiso mínimo en todos los formatos.

---

## 11. Próximo paso

1. Cliente aprueba (o ajusta) este plan.
2. Se autoriza el commit de `docs/rediseno/00-plan.md` y `docs/rediseno/01-auditoria.md`.
3. Arranca **Fase 0**: fix del bug de `font-sans` (1 h), con su verificación.
