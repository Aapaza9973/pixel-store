# SKILL: crud-module — Patrón de CRUD Completo

> **Versión**: 1.0
> **Proyecto**: Pixel Store
> **Aplica a**: Cualquier módulo del panel admin que requiera CRUD (create, read, update, delete)
> **Complementa a**: `skills/hu-development.md`

---

## 0. PROPÓSITO

Esta skill define el **patrón exacto** para construir un CRUD completo en Pixel Store: modelo, migración, policy, form requests, controlador, rutas, vistas, tests y sidebar.

Se aplica a módulos como:
- Marcas, Atributos técnicos, Valores de atributo
- Proveedores, Órdenes de compra
- Cotizaciones, Números de serie
- Cualquier otro CRUD del panel admin

---

## 1. ESTRUCTURA OBLIGATORIA DE ARCHIVOS

Para un CRUD de la entidad `MiEntidad` (tabla `mi_entidades`):

```
app/
├── Http/
│   ├── Controllers/Admin/
│   │   └── MiEntidadController.php       # authorizeResource en constructor
│   └── Requests/Admin/
│       ├── StoreMiEntidadRequest.php     # Crear
│       └── UpdateMiEntidadRequest.php    # Editar
├── Models/
│   └── MiEntidad.php                     # fillable, casts, relaciones, scopes
└── Policies/
    └── MiEntidadPolicy.php               # viewAny, view, create, update, delete

resources/views/admin/mi-entidades/
├── index.blade.php                       # Listado con filtros + paginación
├── create.blade.php                      # Formulario crear
├── edit.blade.php                        # Formulario editar
└── show.blade.php                        # Detalle

tests/Feature/
└── MiEntidadTest.php                     # Al menos 5 tests

database/seeders/
└── MiEntidadSeeder.php                   # Datos demo (si aplica)

database/schema/pgsql-schema.sql          # Actualizado si hay tabla nueva
```

---

## 2. CONVENCIÓN DE NOMBRES

| Concepto | Convención | Ejemplo |
|---|---|---|
| Tabla | Plural snake_case español | `mi_entidades` |
| Modelo | Singular PascalCase español | `MiEntidad` |
| Controlador | Singular + Controller | `MiEntidadController` |
| Policy | Singular + Policy | `MiEntidadPolicy` |
| FormRequest Store | Store + Singular + Request | `StoreMiEntidadRequest` |
| FormRequest Update | Update + Singular + Request | `UpdateMiEntidadRequest` |
| Carpeta vistas | Plural kebab-case español | `mi-entidades` |
| Ruta (URI) | Plural kebab-case | `admin/mi-entidades` |
| Ruta (name) | `admin.mi-entidades.*` | `admin.mi-entidades.index` |
| Permisos | ver/crear/editar/eliminar + plural | `ver mi-entidades` |
| Parámetro ruta | Singular | `{mi_entidad}` |

⚠️ **Cuidado con la singularización**: Laravel usa reglas inglesas. Si el plural español no singulariza bien (ej. `ubicaciones` → `ubicacione`), corregir en la ruta:

```php
Route::resource('mi-entidades', MiEntidadController::class)
    ->parameters(['mi-entidades' => 'mi_entidad']);
```

---

## 3. PATRONES DETALLADOS POR ARTEFACTO

### 3.1. MODELO

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Descripción breve.
 *
 * @property int $id
 * @property string $nombre
 * @property bool $activo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MiEntidad extends Model
{
    use HasFactory;

    protected $table = 'mi_entidades';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ============ RELACIONES ============

    public function hijos(): HasMany
    {
        return $this->hasMany(Hijo::class);
    }

    // ============ SCOPES ============

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where('nombre', 'ILIKE', "%{$termino}%");
    }

    // ============ MÉTODOS DE NEGOCIO ============

    public function estaActiva(): bool
    {
        return (bool) $this->activo;
    }

    public function tieneDependencias(): bool
    {
        return $this->hijos()->exists();
    }
}
```

**Checklist del modelo**:
- [ ] `declare(strict_types=1)`
- [ ] PHPDoc con `@property` de cada campo
- [ ] `$table` si no sigue la convención
- [ ] `$fillable` explícito
- [ ] `$casts` tipados
- [ ] Relaciones con return types
- [ ] Scopes `activas()` y `buscar()` si aplica
- [ ] Método `tieneDependencias()` si bloquea el borrado

---

### 3.2. MIGRACIÓN (solo si es tabla nueva)

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
        Schema::create('mi_entidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255)->unique();
            $table->string('descripcion', 500)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mi_entidades');
    }
};
```

**Reglas**:
- [ ] Idempotente con `Schema::hasTable()` si aplica
- [ ] `up()` y `down()` completos
- [ ] Índices en columnas de búsqueda
- [ ] **Actualizar también `database/schema/pgsql-schema.sql`** con el mismo bloque `CREATE TABLE`

---

### 3.3. POLICY

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MiEntidad;
use App\Models\User;

class MiEntidadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ver mi-entidades');
    }

    public function view(User $user, MiEntidad $miEntidad): bool
    {
        return $user->can('ver mi-entidades');
    }

    public function create(User $user): bool
    {
        return $user->can('crear mi-entidades');
    }

    public function update(User $user, MiEntidad $miEntidad): bool
    {
        return $user->can('editar mi-entidades');
    }

    public function delete(User $user, MiEntidad $miEntidad): bool
    {
        return $user->can('eliminar mi-entidades');
    }
}
```

**Registro en `AppServiceProvider::boot()`**:
```php
Gate::policy(MiEntidad::class, MiEntidadPolicy::class);
```

---

### 3.4. FORM REQUESTS

**Store** (`StoreMiEntidadRequest.php`):

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMiEntidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('crear mi-entidades');
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:mi_entidades,nombre'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe un registro con ese nombre.',
            'nombre.max' => 'El nombre no puede superar 255 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activo' => $this->boolean('activo', true),
        ]);
    }
}
```

**Update** (`UpdateMiEntidadRequest.php`):

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMiEntidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('editar mi-entidades');
    }

    public function rules(): array
    {
        $id = $this->route('mi_entidad')->id;

        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('mi_entidades', 'nombre')->ignore($id)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe un registro con ese nombre.',
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

---

### 3.5. CONTROLLER

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMiEntidadRequest;
use App\Http\Requests\Admin\UpdateMiEntidadRequest;
use App\Models\MiEntidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MiEntidadController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(MiEntidad::class, 'mi_entidad');
    }

    public function index(Request $request): View
    {
        $query = MiEntidad::query();

        if ($buscar = $request->input('buscar')) {
            $query->buscar($buscar);
        }

        if ($request->filled('estado')) {
            $request->estado === 'activos'
                ? $query->where('activo', true)
                : $query->where('activo', false);
        }

        $items = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.mi-entidades.index', compact('items'));
    }

    public function create(): View
    {
        return view('admin.mi-entidades.create');
    }

    public function store(StoreMiEntidadRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            MiEntidad::create($request->validated());
        });

        return redirect()
            ->route('admin.mi-entidades.index')
            ->with('success', 'Registro creado exitosamente.');
    }

    public function show(MiEntidad $miEntidad): View
    {
        $miEntidad->load(['hijos']);
        return view('admin.mi-entidades.show', compact('miEntidad'));
    }

    public function edit(MiEntidad $miEntidad): View
    {
        return view('admin.mi-entidades.edit', compact('miEntidad'));
    }

    public function update(UpdateMiEntidadRequest $request, MiEntidad $miEntidad): RedirectResponse
    {
        DB::transaction(function () use ($request, $miEntidad) {
            $miEntidad->update($request->validated());
        });

        return redirect()
            ->route('admin.mi-entidades.index')
            ->with('success', 'Registro actualizado exitosamente.');
    }

    public function destroy(MiEntidad $miEntidad): RedirectResponse
    {
        if ($miEntidad->tieneDependencias()) {
            return redirect()
                ->route('admin.mi-entidades.index')
                ->with('error', 'No se puede eliminar porque tiene dependencias.');
        }

        $miEntidad->delete();

        return redirect()
            ->route('admin.mi-entidades.index')
            ->with('success', 'Registro eliminado exitosamente.');
    }
}
```

**Checklist**:
- [ ] `authorizeResource()` en constructor
- [ ] `index()` con búsqueda, filtros y paginación
- [ ] `store()` con `DB::transaction()`
- [ ] `update()` con `DB::transaction()`
- [ ] `destroy()` con guard de dependencias
- [ ] `redirect()->with('success'|'error', ...)` en todas las mutaciones
- [ ] Return types explícitos

---

### 3.6. RUTAS

**Ubicación**: `routes/web.php` dentro del grupo `admin`:

```php
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('mi-entidades', \App\Http\Controllers\Admin\MiEntidadController::class)
        ->parameters(['mi-entidades' => 'mi_entidad']);
});
```

**Verificar**:
```bash
php artisan route:list --name=admin.mi-entidades
```

Debe mostrar 7 rutas con el parámetro `{mi_entidad}` (no `{mi_entidade}`).

---

### 3.7. VISTAS

#### 3.7.1. `index.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Mi Entidad')
@section('subtitle', 'Gestión de mi entidad')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-400">
            Total: <span class="text-white font-semibold">{{ $items->total() }}</span> registros
        </p>
        @can('crear mi-entidades')
            <a href="{{ route('admin.mi-entidades.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo registro
            </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <x-card>
        <form method="GET" action="{{ route('admin.mi-entidades.index') }}"
              class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label for="buscar" class="block text-xs font-semibold text-slate-400 uppercase mb-1">
                    Buscar
                </label>
                <input type="text" name="buscar" id="buscar" value="{{ request('buscar') }}"
                       placeholder="Nombre..."
                       class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white text-sm focus:border-blue-500 focus:outline-none">
            </div>

            <div>
                <label for="estado" class="block text-xs font-semibold text-slate-400 uppercase mb-1">
                    Estado
                </label>
                <select name="estado" id="estado"
                        class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white text-sm focus:border-blue-500 focus:outline-none">
                    <option value="">Todos</option>
                    <option value="activos" {{ request('estado') === 'activos' ? 'selected' : '' }}>Activos</option>
                    <option value="inactivos" {{ request('estado') === 'inactivos' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    Filtrar
                </button>
                <a href="{{ route('admin.mi-entidades.index') }}"
                   class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                    Limpiar
                </a>
            </div>
        </form>
    </x-card>

    {{-- Tabla --}}
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-3 py-3">Nombre</th>
                        <th class="px-3 py-3">Descripción</th>
                        <th class="px-3 py-3 text-center">Estado</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse ($items as $item)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-3 py-3 text-white font-medium">{{ $item->nombre }}</td>
                            <td class="px-3 py-3 text-slate-400">{{ $item->descripcion ?? '—' }}</td>
                            <td class="px-3 py-3 text-center">
                                @if ($item->activo)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-medium rounded-md bg-emerald-500/10 text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-medium rounded-md bg-slate-500/10 text-slate-400">
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.mi-entidades.show', $item) }}"
                                       class="text-xs text-slate-400 hover:text-white font-medium">
                                        Ver
                                    </a>
                                    @can('editar mi-entidades')
                                        <a href="{{ route('admin.mi-entidades.edit', $item) }}"
                                           class="text-xs text-blue-400 hover:text-blue-300 font-medium">
                                            Editar
                                        </a>
                                    @endcan
                                    @can('eliminar mi-entidades')
                                        <form method="POST" action="{{ route('admin.mi-entidades.destroy', $item) }}"
                                              onsubmit="return confirm('¿Eliminar {{ $item->nombre }}?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-400 hover:text-red-300 font-medium">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-12 text-center text-sm text-slate-500">
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

#### 3.7.2. `create.blade.php`

```blade
@extends('layouts.app')

@section('title', 'Nuevo Registro')
@section('subtitle', 'Crear un nuevo registro')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.mi-entidades.store') }}" class="space-y-6">
        @csrf

        <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 space-y-5">
            <h3 class="text-sm font-semibold text-slate-300 uppercase tracking-wider">
                Información
            </h3>

            {{-- Nombre --}}
            <div>
                <label for="nombre" class="block text-sm font-medium text-slate-300 mb-1.5">
                    Nombre <span class="text-red-400">*</span>
                </label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}"
                       class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                @error('nombre') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Descripción --}}
            <div>
                <label for="descripcion" class="block text-sm font-medium text-slate-300 mb-1.5">
                    Descripción
                </label>
                <textarea name="descripcion" id="descripcion" rows="3"
                          class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">{{ old('descripcion') }}</textarea>
                @error('descripcion') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Activo --}}
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="activo" value="1" {{ old('activo', true) ? 'checked' : '' }}
                       class="rounded bg-slate-800 border-slate-700 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-slate-300">Activo</span>
            </label>
        </div>

        {{-- Botones --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                Guardar
            </button>
            <a href="{{ route('admin.mi-entidades.index') }}"
               class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
```

#### 3.7.3. `edit.blade.php`

Igual que `create.blade.php` pero:

```blade
<form method="POST" action="{{ route('admin.mi-entidades.update', $miEntidad) }}">
    @csrf
    @method('PUT')

    {{-- value="{{ old('nombre', $miEntidad->nombre) }}" en cada input --}}
    {{-- @checked(old('activo', $miEntidad->activo)) en el checkbox --}}
</form>
```

#### 3.7.4. `show.blade.php`

```blade
@extends('layouts.app')

@section('title', $miEntidad->nombre)
@section('subtitle', 'Detalle del registro')

@section('content')
<div class="max-w-4xl space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs text-slate-500">ID: {{ $miEntidad->id }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('editar mi-entidades')
                <a href="{{ route('admin.mi-entidades.edit', $miEntidad) }}"
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    Editar
                </a>
            @endcan
            <a href="{{ route('admin.mi-entidades.index') }}"
               class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-medium rounded-lg transition">
                Volver
            </a>
        </div>
    </div>

    <x-card title="Información">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Nombre</dt>
                <dd class="text-white">{{ $miEntidad->nombre }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Estado</dt>
                <dd class="{{ $miEntidad->activo ? 'text-emerald-400' : 'text-slate-400' }}">
                    {{ $miEntidad->activo ? 'Activo' : 'Inactivo' }}
                </dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-slate-500 text-xs uppercase tracking-wider mb-1">Descripción</dt>
                <dd class="text-slate-300">{{ $miEntidad->descripcion ?? 'Sin descripción' }}</dd>
            </div>
        </dl>
    </x-card>

</div>
@endsection
```

---

### 3.8. SIDEBAR

Abrir `resources/views/layouts/app.blade.php` y agregar el link (si no existe):

```blade
@can('ver mi-entidades')
    @if (Route::has('admin.mi-entidades.index'))
        <x-nav-link :href="route('admin.mi-entidades.index')"
                    :active="request()->routeIs('admin.mi-entidades.*')"
                    icon="icono">
            Mi Entidad
        </x-nav-link>
    @endif
@endcan
```

**Iconos disponibles**: `home`, `cube`, `tag`, `bookmark`, `adjustments`, `location`, `users`, `clipboard`, `bell`.

Si necesitas otro, agregarlo al array `$icons` en `resources/views/components/nav-link.blade.php`.

---

### 3.9. SECEDER (si aplica)

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MiEntidad;
use Illuminate\Database\Seeder;

class MiEntidadSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['nombre' => 'Item 1', 'descripcion' => 'Descripción 1', 'activo' => true],
            ['nombre' => 'Item 2', 'descripcion' => 'Descripción 2', 'activo' => true],
            // ...
        ];

        foreach ($items as $item) {
            MiEntidad::updateOrCreate(
                ['nombre' => $item['nombre']],
                $item
            );
        }

        $this->command?->info('✅ ' . count($items) . ' registros creados.');
    }
}
```

**Registrar en `DatabaseSeeder`**:
```php
$this->call([
    // ... seeders existentes
    MiEntidadSeeder::class,
]);
```

---

### 3.10. TESTS

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MiEntidad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MiEntidadTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $this->vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();
    }

    public function test_admin_puede_ver_listado(): void
    {
        MiEntidad::create(['nombre' => 'Item Test']);

        $response = $this->actingAs($this->admin)->get(route('admin.mi-entidades.index'));

        $response->assertOk();
        $response->assertSee('Item Test');
    }

    public function test_admin_puede_crear_registro(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.mi-entidades.store'), [
            'nombre' => 'Nuevo Item',
            'descripcion' => 'Descripción',
            'activo' => true,
        ]);

        $response->assertRedirect(route('admin.mi-entidades.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('mi_entidades', ['nombre' => 'Nuevo Item']);
    }

    public function test_admin_puede_editar_registro(): void
    {
        $item = MiEntidad::create(['nombre' => 'Original']);

        $response = $this->actingAs($this->admin)->put(
            route('admin.mi-entidades.update', $item),
            ['nombre' => 'Actualizado', 'activo' => true]
        );

        $response->assertRedirect(route('admin.mi-entidades.index'));
        $this->assertDatabaseHas('mi_entidades', ['id' => $item->id, 'nombre' => 'Actualizado']);
    }

    public function test_admin_puede_eliminar_registro(): void
    {
        $item = MiEntidad::create(['nombre' => 'Eliminar']);

        $response = $this->actingAs($this->admin)->delete(
            route('admin.mi-entidades.destroy', $item)
        );

        $response->assertRedirect(route('admin.mi-entidades.index'));
        $this->assertDatabaseMissing('mi_entidades', ['id' => $item->id]);
    }

    public function test_no_permite_nombre_duplicado(): void
    {
        MiEntidad::create(['nombre' => 'Duplicado']);

        $response = $this->actingAs($this->admin)->post(route('admin.mi-entidades.store'), [
            'nombre' => 'Duplicado',
        ]);

        $response->assertSessionHasErrors('nombre');
    }

    public function test_vendedor_no_puede_crear_registro(): void
    {
        $response = $this->actingAs($this->vendedor)->post(route('admin.mi-entidades.store'), [
            'nombre' => 'Test',
        ]);

        $response->assertForbidden();  // 403
    }
}
```

---

## 4. CHECKLIST COMPLETA DEL CRUD

Antes de reportar OK, verificar:

### Estructura
- [ ] Modelo creado con `$fillable`, `$casts`, relaciones, scopes
- [ ] Migración creada (si aplica) con `up()` y `down()`
- [ ] Schema dump actualizado (si aplica)
- [ ] Policy creada y registrada
- [ ] StoreRequest y UpdateRequest creados
- [ ] Controller creado con `authorizeResource`
- [ ] Rutas registradas con `->parameters()` si hace falta
- [ ] 4 vistas (index, create, edit, show)
- [ ] Link en el sidebar
- [ ] Seeder (si aplica) y registrado en DatabaseSeeder
- [ ] Tests de feature

### Código
- [ ] `declare(strict_types=1)` en todos los PHP
- [ ] Sin `dd()`, `dump()`, `var_dump()`
- [ ] Sin `TODO` sin resolver
- [ ] `./vendor/bin/pint --dirty` sin warnings
- [ ] `php artisan test` sin errores

### Funcional
- [ ] `php artisan route:list --name=admin.mi-entidades` muestra 7 rutas
- [ ] El link aparece en el sidebar con `@can`
- [ ] Crear funciona con validación
- [ ] Editar funciona con `old()`
- [ ] Eliminar pide confirmación
- [ ] Los permisos se respetan (Vendedor → 403)
- [ ] Paginación preserva filtros
- [ ] Estado vacío visible

### UX
- [ ] Mensajes flash en español
- [ ] `@error` debajo de cada input
- [ ] `old()` en todos los campos del form
- [ ] Clases Tailwind coherentes
- [ ] Responsive (grid `md:`)

---

## 5. COMANDOS DE VERIFICACIÓN

```bash
# 1. Limpiar cachés
php artisan optimize:clear

# 2. Ver rutas
php artisan route:list --name=admin.mi-entidades

# 3. Correr tests
php artisan test --filter=MiEntidadTest

# 4. Formatear
./vendor/bin/pint --dirty

# 5. Correr toda la suite (verificar que no rompimos nada)
php artisan test
```

---

## 6. FORMATO DE REPORTE

Al terminar el CRUD, reporta con este formato:

```markdown
## ✅ CRUD de {Mi Entidad} — Completado

### 📁 Archivos
**Creados** (X):
- app/Models/MiEntidad.php
- app/Policies/MiEntidadPolicy.php
- ...

**Modificados** (Y):
- app/Providers/AppServiceProvider.php
- routes/web.php
- resources/views/layouts/app.blade.php

### 🛣️ Rutas (7)
```
GET|HEAD  admin/mi-entidades              → admin.mi-entidades.index
POST      admin/mi-entidades              → admin.mi-entidades.store
GET|HEAD  admin/mi-entidades/create       → admin.mi-entidades.create
GET|HEAD  admin/mi-entidades/{mi_entidad} → admin.mi-entidades.show
PUT|PATCH admin/mi-entidades/{mi_entidad} → admin.mi-entidades.update
DELETE    admin/mi-entidades/{mi_entidad} → admin.mi-entidades.destroy
GET|HEAD  admin/mi-entidades/{mi_entidad}/edit → admin.mi-entidades.edit
```

### 📊 Verificación
- Tests: **X passed (Y assertions)**
- Pint: **PASS N files**
- Suite completa: **X passed**

### 🧪 Tests agregados
1. test_admin_puede_ver_listado
2. test_admin_puede_crear_registro
3. ...

### 🧠 Decisiones tomadas
- ...

### ⚠️ Observaciones
- ...

**Espero tu OK para continuar.**
```

---

## 7. ANTI-PATRONES ESPECÍFICOS DE CRUD

| ❌ Anti-patrón | ✅ Correcto |
|---|---|
| `Route::resource('ubicaciones', ...)` sin `->parameters()` | Usar `->parameters(['ubicaciones' => 'ubicacion'])` |
| `$this->authorize()` manual | `$this->authorizeResource()` en constructor |
| Lógica en `index()` | Delegar a un Service si es complejo |
| Sin `DB::transaction()` en store/update multi-tabla | Envolver con `DB::transaction()` |
| `return view()` sin eager loading | `->with([...])` para evitar N+1 |
| Vistas sin `@can` | `@can` en cada acción sensible |
| Sin mensajes flash | `->with('success'\|'error', '...')` |
| Sin tests de 403 | Test obligatorio de permisos |
| Sin paginación | `->paginate(15)` siempre |
| Sin `withQueryString()` | Preservar filtros al paginar |
| `->name('admin.mi_entidad')` (snake) | `->name('admin.mi-entidades')` (kebab) |

---

## 8. EJEMPLOS DE APLICACIÓN EN PIXEL STORE

CRUDs ya implementados siguiendo este patrón:

| Módulo | Estado | Referencia |
|---|:-:|---|
| Categorías | ✅ | `app/Http/Controllers/Admin/CategoriaController.php` |
| Productos | ✅ | `app/Http/Controllers/Admin/ProductoController.php` |
| Ubicaciones | ✅ | `app/Http/Controllers/Admin/UbicacionController.php` |
| Alertas | ✅ | `app/Http/Controllers/AlertaController.php` |

CRUDs futuros (Sprint 2+):
- Marcas
- Atributos técnicos + valores
- Proveedores
- Órdenes de compra
- Números de serie
- Cotizaciones

---

## 9. RECORDATORIOS FINALES

- 🎯 **Copiá el patrón**: si Categorías funcionó, Marcas también
- 🧪 **Tests primero**: un CRUD sin tests es deuda técnica
- 🚫 **No mezcles CRUDs**: un CRUD por tarea, un commit por CRUD
- 🎨 **Mismo estilo visual**: `bg-slate-900`, `<x-card>`, badges coherentes
- 📝 **Reporta con formato**: el jefe necesita saber qué hiciste
- ⏸️ **Espera el OK**: nunca avances sin confirmación
