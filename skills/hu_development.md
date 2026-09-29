# SKILL: hu-development — Desarrollo de Historias de Usuario

> **Versión**: 1.0
> **Última actualización**: Septiembre 2026
> **Proyecto**: Pixel Store
> **Aplicable a**: Cualquier HU con criterios de aceptación definidos

---

## 0. PROPOSITO

Esta skill le indica al agente (Freebuff CLI) cómo desarrollar **una Historia de Usuario completa** de principio a fin, generando:
- Modelos Eloquent profesionales
- Migraciones idempotentes
- Policies de autorización
- FormRequests con validación completa
- Controladores finos
- Vistas Blade con identidad visual
- Tests que cubren todos los criterios de aceptación
- Documentación actualizada

---

## 1. CONTEXTO DEL PROYECTO

### Stack
- **Backend**: PHP 8.3 + Laravel 13
- **BD**: PostgreSQL 18 (schema dump + migraciones incrementales)
- **Frontend**: Blade + Tailwind CSS + Alpine.js
- **Permisos**: Spatie Laravel-Permission
- **Tests**: PHPUnit 12
- **Formatter**: Laravel Pint

### Estructura de directorios clave
```
app/
├── Http/
│   ├── Controllers/Admin/    ← Controladores del panel interno
│   ├── Middleware/            ← Middlewares personalizados
│   └── Requests/Admin/        ← FormRequests por módulo
├── Models/                    ← 20+ modelos Eloquent
├── Policies/                  ← Autorización por recurso
├── Services/                  ← Lógica de negocio transaccional
├── Observers/                 ← Listeners de modelos
└── Console/Commands/          ← Comandos artisan

database/
├── migrations/                ← Migraciones incrementales
├── schema/pgsql-schema.sql    ← Schema completo (fuente de verdad)
└── seeders/                   ← Datos maestros

resources/views/
├── layouts/app.blade.php      ← Layout principal
├── components/                ← Componentes Blade (x-card, x-alert, etc.)
└── admin/                     ← Vistas del panel interno

tests/
└── Feature/                   ← Feature tests (con DatabaseTransactions)
```

### Roles del sistema
`Admin`, `Vendedor`, `Cajero`, `Inventario`, `Cliente`

### Convenciones de dominio
- **Entidades en español**: `Producto`, `Venta`, `Cotizacion`, `Ubicacion`
- **Campos en snake_case español**: `precio_unitario`, `nit_ci`, `creado_en`
- **Mensajes al usuario en español**
- **Permisos en español**: `ver productos`, `crear usuarios`

---

## 2. REGLAS DE TRABAJO (obligatorias)

### 2.1. Trabaja UNA tarea a la vez
- Lee la tarea completa antes de empezar.
- Al terminar, muestra el reporte (ver §8) y **ESPERA MI OK**.
- No empieces la siguiente tarea hasta recibir confirmación explícita.

### 2.2. Antes de empezar cualquier tarea
1. Lee `AGENTS.md` completo.
2. Lee esta skill completa.
3. Lee la HU completa y sus criterios de aceptación.
4. Identifica qué ya existe y qué falta.
5. Si algo es ambiguo, PREGUNTA antes de asumir.

### 2.3. Nunca hagas esto sin pedir permiso
- ❌ Ejecutar `migrate:fresh` (destruye datos)
- ❌ Modificar `.env` ni credenciales
- ❌ Cambiar el schema dump sin migración
- ❌ Instalar paquetes composer/npm nuevos
- ❌ Modificar archivos fuera del scope de la tarea
- ❌ Borrar archivos existentes sin backup
- ❌ Hacer commit o push (eso lo hace el usuario)

### 2.4. Al detectar un bug existente
Si durante la tarea encuentras un bug **fuera del scope**:
1. Documéntalo en el reporte final.
2. **NO lo arregles** sin autorización explícita.
3. Sugiere cómo arreglarlo para que se planifique.

### 2.5. Idioma
- **Código**: nombres de variables/métodos en español cuando representen dominio (`$producto`, `$precioUnitario`)
- **Palabras reservadas de Laravel**: en inglés (`$fillable`, `$casts`, `public function store()`)
- **Comentarios**: español, solo cuando aportan valor
- **Mensajes de error/éxito**: español
- **Tests**: nombres descriptivos en español (`test_admin_puede_crear_producto`)

---

## 3. PATRONES POR TIPO DE ARTEFACTO

### 3.1. MODELO ELOQUENT

**Estructura obligatoria**:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Descripción breve del modelo.
 *
 * @property int $id
 * @property string $nombre
 * @property bool $activo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MiModelo extends Model
{
    use HasFactory;

    protected $table = 'mi_tabla';  // solo si no sigue la convención

    protected $fillable = [
        'nombre', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'precio' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    // ============ RELACIONES ============
    public function otraEntidad(): BelongsTo
    {
        return $this->belongsTo(OtraEntidad::class);
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(Hijo::class);
    }

    // ============ SCOPES ============
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'ILIKE', "%{$termino}%")
              ->orWhere('email', 'ILIKE', "%{$termino}%");
        });
    }

    // ============ MÉTODOS DE NEGOCIO ============
    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }
}
```

**Reglas**:
- ✅ `declare(strict_types=1)` al inicio
- ✅ PHPDoc con `@property`
- ✅ `$fillable` explícito (nunca `$guarded = []`)
- ✅ `$casts` tipados (`decimal:2`, `boolean`, `datetime`, `array`, `hashed`)
- ✅ Relaciones con **return types** (`BelongsTo`, `HasMany`, `BelongsToMany`)
- ✅ Scopes para filtros recurrentes
- ✅ Métodos helper con return type (`: bool`, `: string`)
- ❌ NO incluir lógica de negocio compleja (va en Services)

---

### 3.2. MIGRACIÓN

**Estructura obligatoria**:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mi_tabla', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('email', 255)->unique();
            $table->boolean('activo')->default(true);
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mi_tabla');
    }
};
```

**Si es ALTER TABLE** (agregar columnas):

```php
public function up(): void
{
    foreach (['columna_a', 'columna_b'] as $columna) {
        if (! Schema::hasColumn('mi_tabla', $columna)) {
            Schema::table('mi_tabla', function (Blueprint $table) use ($columna) {
                $table->string($columna, 50)->nullable();
            });
        }
    }
}
```

**Reglas**:
- ✅ Idempotentes (guardar con `hasColumn` / `hasTable`)
- ✅ `up()` y `down()` completos
- ✅ Índices explícitos en columnas de búsqueda
- ✅ FK con política correcta: `restrictOnDelete`, `cascadeOnDelete`, `nullOnDelete`
- ✅ `declare(strict_types=1)`
- ⚠️ Si agregas columnas, **también actualiza** `database/schema/pgsql-schema.sql`

---

### 3.3. POLICY

**Estructura obligatoria**:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MiModelo;
use App\Models\User;

class MiModeloPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver mi-modulo');
    }

    public function view(User $user, MiModelo $modelo): bool
    {
        return $user->can('ver mi-modulo');
    }

    public function create(User $user): bool
    {
        return $user->can('crear mi-modulo');
    }

    public function update(User $user, MiModelo $modelo): bool
    {
        return $user->can('editar mi-modulo');
    }

    public function delete(User $user, MiModelo $modelo): bool
    {
        return $user->can('eliminar mi-modulo');
    }
}
```

**Reglas**:
- ✅ Un método por acción estándar
- ✅ Cada método verifica un permiso de Spatie con `$user->can('...')`
- ✅ Si hay reglas de negocio (auto-borrado, último admin), agregarlas
- ✅ Registrar la policy en `AppServiceProvider::boot()`

```php
// AppServiceProvider::boot()
Gate::policy(MiModelo::class, MiModeloPolicy::class);
```

---

### 3.4. FORM REQUEST

**Estructura obligatoria**:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMiModeloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear mi-modulo');
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:mi_tabla,nombre'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe un registro con ese nombre.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'El email ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'roles.required' => 'Debe asignar al menos un rol.',
            'roles.min' => 'Debe asignar al menos un rol.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activo' => $this->boolean('activo'),
        ]);
    }
}
```

**Para `UpdateMiModeloRequest`**:

```php
public function rules(): array
{
    $modeloId = $this->route('mi_modelo')->id;

    return [
        'nombre' => ['required', 'string', 'max:255', Rule::unique('mi_tabla')->ignore($modeloId)],
        // ... resto igual
    ];
}
```

**Reglas**:
- ✅ `authorize()` verifica permiso específico
- ✅ `rules()` con validaciones estrictas
- ✅ `messages()` **todos en español**
- ✅ `prepareForValidation()` para normalizar booleanos
- ✅ Update usa `Rule::unique()->ignore()` para no chocar con sí mismo
- ✅ `declare(strict_types=1)`

---

### 3.5. CONTROLLER

**Estructura obligatoria**:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMiModeloRequest;
use App\Http\Requests\Admin\UpdateMiModeloRequest;
use App\Models\MiModelo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MiModeloController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(MiModelo::class, 'mi_modelo');
    }

    public function index(): View
    {
        $items = MiModelo::query()
            ->with(['relacion1', 'relacion2'])
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.mi-modulo.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.mi-modulo.create');
    }

    public function store(StoreMiModeloRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            MiModelo::create($request->validated());
        });

        return redirect()
            ->route('admin.mi-modulo.index')
            ->with('success', 'Registro creado exitosamente.');
    }

    public function show(MiModelo $miModelo): View
    {
        $miModelo->load(['relacion1', 'relacion2']);
        return view('admin.mi-modulo.show', compact('miModelo'));
    }

    public function edit(MiModelo $miModelo): View
    {
        return view('admin.mi-modulo.edit', compact('miModelo'));
    }

    public function update(UpdateMiModeloRequest $request, MiModelo $miModelo): RedirectResponse
    {
        DB::transaction(function () use ($request, $miModelo) {
            $miModelo->update($request->validated());
        });

        return redirect()
            ->route('admin.mi-modulo.index')
            ->with('success', 'Registro actualizado exitosamente.');
    }

    public function destroy(MiModelo $miModelo): RedirectResponse
    {
        // Verificar reglas de negocio antes de eliminar
        if ($miModelo->tieneDependencias()) {
            return redirect()
                ->route('admin.mi-modulo.index')
                ->with('error', 'No se puede eliminar porque tiene dependencias.');
        }

        $miModelo->delete();

        return redirect()
            ->route('admin.mi-modulo.index')
            ->with('success', 'Registro eliminado exitosamente.');
    }
}
```

**Reglas**:
- ✅ `declare(strict_types=1)`
- ✅ `authorizeResource()` en el constructor
- ✅ Métodos con **return types** (`: View`, `: RedirectResponse`)
- ✅ `DB::transaction()` para operaciones multi-tabla
- ✅ `with('success'|'error', '...')` para mensajes flash
- ✅ Eager loading en `index()` y `show()` para evitar N+1
- ✅ Si el parámetro de ruta se mal-singulariza (ej. `ubicaciones` → `ubicacione`), corregirlo:
  ```php
  Route::resource('ubicaciones', UbicacionController::class)
      ->parameters(['ubicaciones' => 'ubicacion']);
  ```

---

### 3.6. SERVICE

**Estructura obligatoria**:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MiModelo;
use Illuminate\Support\Facades\DB;

class MiModeloServicio
{
    /**
     * Descripción de qué hace este método.
     *
     * @throws \DomainException Si algo falla
     */
    public function hacerAlgo(MiModelo $modelo, int $cantidad): void
    {
        DB::transaction(function () use ($modelo, $cantidad) {
            // Bloqueo pesimista
            $fresh = MiModelo::where('id', $modelo->id)->lockForUpdate()->firstOrFail();

            // Validaciones de negocio
            if ($fresh->stock < $cantidad) {
                throw new \DomainException('Stock insuficiente.');
            }

            // Mutaciones atómicas
            $fresh->update(['stock' => $fresh->stock - $cantidad]);
        });
    }
}
```

**Reglas**:
- ✅ `declare(strict_types=1)`
- ✅ PHPDoc con `@throws` si aplica
- ✅ `DB::transaction()` siempre que haya múltiples mutaciones
- ✅ `lockForUpdate()` para prevenir condiciones de carrera
- ✅ Excepciones de dominio (`\DomainException`) para errores de negocio
- ✅ Retornar valores tipados, no mixtos
- ✅ Sin dependencias a `Request` (recibir datos primitivos o modelos)

---

### 3.7. VISTA BLADE

**Estructura obligatoria**:

```blade
@extends('layouts.app')

@section('title', 'Mi Módulo')
@section('subtitle', 'Descripción breve')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-white tracking-tight">Mi Módulo</h2>
        @can('crear mi-modulo')
            <a href="{{ route('admin.mi-modulo.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                Nuevo registro
            </a>
        @endcan
    </div>

    {{-- Tabla --}}
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-3 py-3">Columna 1</th>
                        <th class="px-3 py-3">Columna 2</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($items as $item)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-3 py-3 text-white">{{ $item->campo1 }}</td>
                            <td class="px-3 py-3 text-slate-400">{{ $item->campo2 }}</td>
                            <td class="px-3 py-3 text-right">
                                @can('editar mi-modulo')
                                    <a href="{{ route('admin.mi-modulo.edit', $item) }}"
                                       class="text-xs text-blue-400 hover:text-blue-300 font-medium">
                                        Editar
                                    </a>
                                @endcan
                                @can('eliminar mi-modulo')
                                    <form method="POST" action="{{ route('admin.mi-modulo.destroy', $item) }}"
                                          onsubmit="return confirm('¿Eliminar?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-400 hover:text-red-300 font-medium">
                                            Eliminar
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-12 text-center text-sm text-slate-500">
                                No hay registros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
    </x-card>
</div>
@endsection
```

**Reglas**:
- ✅ Extender `layouts.app`
- ✅ Usar `@section('title')`, `@section('subtitle')`, `@section('content')`
- ✅ Usar `<x-card>`, `<x-alert>`, `<x-nav-link>`
- ✅ Clases Tailwind: `bg-slate-900`, `border-slate-800`, `text-white`, `bg-blue-600`
- ✅ `@can` para ocultar acciones sin permiso
- ✅ `old('campo', $modelo->campo)` en formularios
- ✅ `@error('campo')` debajo de cada input
- ✅ `onclick="return confirm('...')"` en formularios DELETE
- ✅ Estado vacío con `@forelse @empty @endforelse`
- ✅ Paginación con `{{ $items->links() }}`
- ❌ NO usar Bootstrap
- ❌ NO incluir lógica pesada (calcular en el controlador)

---

### 3.8. TEST

**Estructura obligatoria**:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MiModuloTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_admin_puede_ver_listado(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.mi-modulo.index'));

        $response->assertOk();
        $response->assertSee('Mi Módulo');
    }

    public function test_vendedor_no_puede_crear(): void
    {
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $response = $this->actingAs($vendedor)->post(route('admin.mi-modulo.store'), [
            'nombre' => 'Test',
        ]);

        $response->assertForbidden();  // 403
    }
}
```

**Reglas**:
- ✅ `declare(strict_types=1)`
- ✅ `use DatabaseTransactions` (NO `RefreshDatabase` — más rápido)
- ✅ `setUp()` con `seed(RoleSeeder::class)` si necesita usuarios
- ✅ Nombres de tests descriptivos en español
- ✅ Al menos 1 test por criterio de aceptación
- ✅ Cubrir siempre:
  - Happy path (éxito)
  - Caso 403 (sin permiso)
  - Validación (email duplicado, etc.)
  - Redirects correctos
- ✅ `assertDatabaseHas` / `assertDatabaseMissing` para verificar BD
- ✅ Usar los usuarios demo (`admin@pixelstore.com`, `vendedor@pixelstore.com`)

---

### 3.9. OBSERVER (si aplica)

**Estructura obligatoria**:

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LogAuditoria;
use App\Models\MiModelo;

class MiModeloObserver
{
    public function created(MiModelo $modelo): void
    {
        $this->registrar('crear_' . $modelo->getTable(), $modelo, []);
    }

    public function updated(MiModelo $modelo): void
    {
        $this->registrar(
            'editar_' . $modelo->getTable(),
            $modelo,
            $modelo->getChanges()
        );
    }

    public function deleted(MiModelo $modelo): void
    {
        $this->registrar('eliminar_' . $modelo->getTable(), $modelo, []);
    }

    private function registrar(string $accion, MiModelo $modelo, array $cambios): void
    {
        if (! auth()->check()) {
            return;
        }

        LogAuditoria::create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'modelo' => MiModelo::class,
            'modelo_id' => $modelo->id,
            'datos_anteriores' => $modelo->getOriginal(),
            'datos_nuevos' => $cambios,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

**Registro en `AppServiceProvider::boot()`**:
```php
MiModelo::observe(MiModeloObserver::class);
```

---

### 3.10. MIDDLEWARE (si aplica)

**Estructura obligatoria**:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && /* condición */) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Mensaje de error.');
        }

        return $next($request);
    }
}
```

**Registro en `bootstrap/app.php`**:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'mi.middleware' => \App\Http\Middleware\MiMiddleware::class,
    ]);
})
```

---

## 4. CHECKLIST POR TAREA

Al desarrollar **cada tarea del Sprint Backlog**, verifica esta lista antes de reportar OK.

### 4.1. Checklist general (aplica a TODA tarea)

- [ ] Leí `AGENTS.md` y esta skill
- [ ] Leí la tarea y sus subtareas completas
- [ ] Identifiqué dependencias previas
- [ ] No modifiqué archivos fuera del scope
- [ ] Usé `declare(strict_types=1)` en archivos PHP nuevos
- [ ] Corrí `./vendor/bin/pint --dirty` sin errores
- [ ] Corrí `php artisan test` sin errores
- [ ] No dejé `dd()`, `dump()`, `var_dump()` en el código
- [ ] No dejé `TODO` sin resolver
- [ ] El código tiene PHPDoc donde aporta valor

### 4.2. Checklist por tipo de artefacto

**Modelo**:
- [ ] `$fillable` explícito
- [ ] `$casts` tipados
- [ ] `$hidden` para campos sensibles (password, tokens)
- [ ] Relaciones con return types
- [ ] Scopes para filtros recurrentes
- [ ] Métodos helper con return types

**Migración**:
- [ ] Idempotente
- [ ] `up()` y `down()` completos
- [ ] Índices en columnas de búsqueda
- [ ] FK con política correcta
- [ ] Si altera tabla, actualicé el schema dump

**Policy**:
- [ ] Un método por acción
- [ ] Cada método verifica permiso
- [ ] Reglas de negocio específicas si aplican
- [ ] Registrada en `AppServiceProvider`

**FormRequest**:
- [ ] `authorize()` con permiso
- [ ] `rules()` completas
- [ ] `messages()` todos en español
- [ ] `prepareForValidation()` si normaliza datos
- [ ] Update usa `Rule::unique()->ignore()`

**Controller**:
- [ ] `authorizeResource()` en constructor
- [ ] Return types en todos los métodos
- [ ] `DB::transaction()` si multi-tabla
- [ ] Mensajes flash con `with('success'|'error')`
- [ ] Eager loading para evitar N+1

**Vista**:
- [ ] Extiende `layouts.app`
- [ ] Usa `<x-card>`, `<x-alert>`
- [ ] Clases Tailwind coherentes
- [ ] `@can` para acciones sensibles
- [ ] `old()` y `@error` en formularios
- [ ] Estado vacío con `@forelse @empty`
- [ ] Paginación si aplica
- [ ] Sin Bootstrap

**Test**:
- [ ] `DatabaseTransactions` (no RefreshDatabase)
- [ ] `seed(RoleSeeder::class)` en setUp si necesita
- [ ] Cubre happy path
- [ ] Cubre caso 403 (sin permiso)
- [ ] Cubre validaciones
- [ ] Nombres descriptivos en español

---

## 5. FORMATO DE REPORTE AL TERMINAR CADA TAREA

Al completar una tarea, reporta **exactamente** con este formato:

```markdown
## ✅ Tarea {ID} — {Nombre de la tarea}

### 📁 Archivos
**Creados**:
- app/Models/MiModelo.php
- app/Policies/MiModeloPolicy.php
- ...

**Modificados**:
- app/Providers/AppServiceProvider.php
- routes/web.php
- ...

### 🔧 Comandos ejecutados
```bash
php artisan optimize:clear
php artisan test --filter=MiModuloTest
./vendor/bin/pint --dirty
```

### 📊 Resultados
- Tests: **X passed (Y assertions)**
- Pint: **PASS N files**
- Rutas registradas: X

### 🧠 Decisiones tomadas
- Elegí X porque Y
- Comentario sobre Z

### ⚠️ Bugs encontrados fuera de scope
- Bug 1: descripción y sugerencia de fix
- Bug 2: ...

### 🚧 Pendientes / Bloqueantes
- Necesito confirmación sobre X

### ❓ Preguntas (si hay)
- ¿Cómo manejo Y?
- ¿Prefieres Z o W?

**Espero tu OK para continuar con la siguiente tarea.**
```

**Reglas del reporte**:
- ✅ Sé conciso pero completo
- ✅ Incluye el output exacto de los tests
- ✅ Documenta decisiones no obvias
- ✅ Menciona cualquier bug encontrado
- ✅ **ESPERA el OK** antes de continuar

---

## 6. DEFINITION OF DONE (DoD)

Una tarea se considera **TERMINADA** cuando:

- ✅ El código está escrito y sigue los patrones de esta skill
- ✅ Pasa `php artisan test` sin errores
- ✅ Pasa `./vendor/bin/pint --dirty` sin warnings
- ✅ Tiene tests que cubren los criterios de aceptación
- ✅ La funcionalidad fue probada manualmente (o tiene tests que la cubren)
- ✅ Sin `dd()`, `dump()`, `var_dump()`, `TODO` sin resolver
- ✅ Sin cambios en `.env`, `.git/`, `vendor/`, `node_modules/`
- ✅ La documentación relevante está actualizada
- ✅ El usuario dio su OK explícito

---

## 7. ANTI-PATRONES A EVITAR

| ❌ Anti-patrón | ✅ En su lugar |
|---|---|
| Lógica de negocio en controlador | Moverla a un Service |
| `$guarded = []` | `$fillable` explícito |
| `where('activo', true)` repetido | Scope `activos()` |
| `$producto->stock = X; $producto->save();` | `InventoryService::ajustarStock()` |
| `DB::raw()` sin sanitizar | Eloquent o bindings |
| Hardcodear IDs (`'categoria_id' => 5`) | Usar `Categoria::firstOrCreate()` |
| Mezclar idiomas (`Product` vs `Producto`) | Un solo idioma por dominio |
| Dejar `dd()` olvidados | Usar `logger()` o tests |
| Vistas sin `@can` | Siempre `@can` en acciones sensibles |
| Tests con `RefreshDatabase` | `DatabaseTransactions` |
| Commitear `.env` | Está en `.gitignore` |
| Cambiar el schema dump sin migración | Ambas cosas juntas |
| Ignorar la singularización de rutas | Usar `->parameters(['x' => 'y'])` |
| `session()->regenerate()` para extender | Usar `save()` (preserva CSRF) |
| `beforeunload` para cleanup | `pagehide`/`pageshow` (bfcache) |

---

## 8. COMANDOS ÚTILES

```bash
# Ver rutas del módulo
php artisan route:list --name=admin.mi-modulo

# Correr tests del módulo
php artisan test --filter=MiModuloTest

# Formatear solo archivos modificados
./vendor/bin/pint --dirty

# Limpiar todas las cachés
php artisan optimize:clear

# Ver el estado de la BD
php artisan db:show

# Tinker interactivo
php artisan tinker

# Ver si la migración quedó registrada
php artisan migrate:status

# Aplicar migraciones pendientes
php artisan migrate
```

---

## 9. FLUJO TÍPICO DE UNA TAREA

```
1. [AGENTE] Lee HU + tarea + esta skill
2. [AGENTE] Identifica dependencias
3. [AGENTE] Pregunta si hay ambigüedades
4. [USUARIO] Responde
5. [AGENTE] Desarrolla la tarea
6. [AGENTE] Corre tests + pint
7. [AGENTE] Reporta con formato §5
8. [USUARIO] Revisa y da OK (o pide cambios)
9. [AGENTE] Si OK → siguiente tarea
            Si cambios → ajusta y vuelve al punto 6
```

---

## 10. RECORDATORIOS FINALES

- 🎯 **Calidad > velocidad**: mejor una tarea bien hecha que dos a medias
- 🧪 **Tests primero**: si el criterio no está testeado, no está terminado
- 📝 **Documenta decisiones**: el "por qué" importa tanto como el "qué"
- 🛑 **Pregunta antes de asumir**: 2 minutos de pregunta ahorra 2 horas de rehacer
- ⏸️ **Espera el OK**: nunca avances sin confirmación
- 🔄 **Un commit por tarea**: no mezcles cambios de varias tareas
- 🎨 **Consistencia visual**: mismo diseño, mismos componentes, mismo idioma
- 🚀 **Itera**: mejor entregar algo funcional y mejorarlo que esperar a que sea perfecto
