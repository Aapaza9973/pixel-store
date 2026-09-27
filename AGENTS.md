# SKILL: crud-module — Patrón estándar de Pixel Store

## Propósito
Todo CRUD del sistema (Categorías, Marcas, Productos, etc.) DEBE seguir este patrón 
para mantener consistencia, calidad y seguridad profesional.

## Estructura obligatoria de archivos
app/
├── Http/
│ ├── Controllers/Admin/
│ │ └── {Modelo}Controller.php # authorizeResource en constructor
│ ├── Requests/Admin/
│ │ ├── Store{Modelo}Request.php # authorize() + rules() + messages()
│ │ └── Update{Modelo}Request.php # Rule::unique()->ignore()
├── Models/
│ └── {Modelo}.php # 

fillable,
fillable,casts, relaciones, scopes
└── Policies/
└── {Modelo}Policy.php # viewAny, view, create, update, delete

resources/views/admin/{modelos}/
├── index.blade.php # Tabla + filtros + paginación
├── create.blade.php # Formulario con @error
├── edit.blade.php # Formulario con old() y valores actuales
└── show.blade.php # Detalle con <x-card>

database/seeders/
└── {Modelo}Seeder.php # Datos demo con firstOrCreate

tests/Feature/
└── {Modelo}Test.php # CRUD + autorización

## Convenciones de código

### Controlador
- Namespace: `App\Http\Controllers\Admin`
- Extiende `App\Http\Controllers\Controller`
- En `__construct()`: `$this->authorizeResource({Modelo}::class, '{modelo}')`
- Métodos tipados con `: View` y `: RedirectResponse`
- Transacciones con `DB::transaction()` cuando afecta múltiples tablas
- Redirección con `->with('success'|'error', '...')`

### FormRequests
- Namespace: `App\Http\Requests\Admin`
- `authorize()`: verifica permiso específico (`crear {modelos}`, `editar {modelos}`)
- `rules()`: validaciones estrictas (unique, exists, numeric, min, max)
- `messages()`: mensajes en español, claros y específicos
- `prepareForValidation()`: para normalizar booleanos y campos opcionales

### Modelos
- `$fillable` explícito (nunca `$guarded = []`)
- `$casts` tipados: `decimal:2`, `boolean`, `array`, `datetime`
- Relaciones con tipado de retorno (`BelongsTo`, `HasMany`)
- Scopes reutilizables: `buscar()`, `activos()`, `stockBajo()`
- Métodos helper: `tieneStockBajo()`, `estaActivo()`

### Policy
- Un método por acción: `viewAny`, `view`, `create`, `update`, `delete`
- Cada método verifica un permiso de Spatie (`$user->can('...')`)
- Registro en `AppServiceProvider::boot()` con `Gate::policy()`

### Vistas Blade
- Extiende `layouts.app`
- Usa `@section('title')`, `@section('subtitle')`, `@section('content')`
- Componentes obligatorios: `<x-card>`, `<x-alert>`, `<x-nav-link>`
- Clases Tailwind: fondo `bg-slate-900`, bordes `border-slate-800`, texto `text-white`
- Botones primarios: `bg-blue-600 hover:bg-blue-700`
- Botones peligro: `text-red-400 hover:text-red-300`
- Todos los textos en español
- `@can` para mostrar/ocultar acciones según permisos
- `@error` debajo de cada input
- `old()` en formularios para persistir datos al fallar validación

### Seeder
- Usa `firstOrCreate()` para ser idempotente (se puede correr N veces)
- Datos realistas: marcas reales (Intel, AMD, ASUS), categorías del rubro
- Mensaje con `$this->command->info('✅ N {modelos} creados.')`

### Tests
- `use RefreshDatabase;` o `use DatabaseTransactions;`
- Cubre: index, create, store, update, destroy
- Cubre: 403 para roles sin permiso
- Cubre: validación de campos únicos
- Mínimo 5 tests por CRUD

## Checklist de calidad (Definition of Done)

- [ ] Las 7 rutas aparecen en `php artisan route:list --name=admin.{modelos}`
- [ ] El link aparece en el sidebar con `@can` + `Route::has`
- [ ] La Policy está registrada en `AppServiceProvider`
- [ ] Los FormRequests autorizan con permiso específico
- [ ] Las vistas usan componentes `<x-card>` y `<x-alert>`
- [ ] El listado tiene paginación
- [ ] Los tests pasan: `php artisan test --filter={Modelo}Test`
- [ ] El código pasa Pint: `./vendor/bin/pint --dirty`
- [ ] No hay errores en `php artisan optimize:clear` + `php artisan serve`

## Anti-patrones a evitar

- ❌ Lógica de negocio en el controlador (debe ir en `app/Services/`)
- ❌ `DB::raw()` sin sanitizar (usar Eloquent o bindings)
- ❌ Hardcodear IDs o valores mágicos
- ❌ Mezclar idiomas (código en inglés, pero entidades/mensajes en español)
- ❌ `dd()` o `dump()` olvidados en el código
- ❌ Bootstrap (usar solo Tailwind)
- ❌ Vistas sin `@can` cuando la acción es sensible
