# 04 — Roles y Permisos

Sistema de autorización basado en Spatie Laravel-Permission.

---

## 👥 Roles del sistema

| Rol | Descripción | Acceso | Permisos |
|---|---|---|:-:|
| 👑 **Admin** | Dueño / Gerente | Todo (super-admin vía `Gate::before`) | 68 (todos) |
| 💼 **Vendedor** | Personal de ventas | Ventas, clientes, cotizaciones, caja | 17 |
| 💰 **Cajero** | Encargado de caja | Ventas, clientes, caja | 8 |
| 📦 **Inventario** | Encargado de almacén | Productos, categorías, marcas, atributos, ubicaciones, proveedores | 33 |
| 🧑 **Cliente** | Cliente registrado | Catálogo y sus cotizaciones | 2 |

---

## 🔑 Permisos por módulo

**68 permisos** en total, agrupados por módulo. La matriz se extrae de
`database/seeders/RoleSeeder.php`, que es la fuente de verdad.

> ℹ️ Esta matriz se verificó contra la BD (`Role::with('permissions')`) al
> cerrar HU-1.1. Las filas marcadas con — significan que el rol **no** tiene ese
> permiso.

### Inventario / Catálogo

| Permiso | Admin | Vendedor | Cajero | Inventario | Cliente |
|---|:-:|:-:|:-:|:-:|:-:|
| `ver productos` | ✅ | ✅ | ✅ | ✅ | — |
| `ver inventario` | ✅ | — | — | ✅ | — |
| `crear productos` | ✅ | — | — | ✅ | — |
| `editar productos` | ✅ | — | — | ✅ | — |
| `eliminar productos` | ✅ | — | — | ✅ | — |
| `ver categorias` | ✅ | — | — | ✅ | — |
| `crear categorias` | ✅ | — | — | ✅ | — |
| `editar categorias` | ✅ | — | — | ✅ | — |
| `eliminar categorias` | ✅ | — | — | ✅ | — |
| `ver marcas` | ✅ | — | — | ✅ | — |
| `crear marcas` | ✅ | — | — | ✅ | — |
| `editar marcas` | ✅ | — | — | ✅ | — |
| `eliminar marcas` | ✅ | — | — | ✅ | — |
| `ver atributos` | ✅ | — | — | ✅ | — |
| `crear atributos` | ✅ | — | — | ✅ | — |
| `editar atributos` | ✅ | — | — | ✅ | — |
| `eliminar atributos` | ✅ | — | — | ✅ | — |
| `ver ubicaciones` | ✅ | — | — | ✅ | — |
| `crear ubicaciones` | ✅ | — | — | ✅ | — |
| `editar ubicaciones` | ✅ | — | — | ✅ | — |
| `eliminar ubicaciones` | ✅ | — | — | ✅ | — |

### Ventas y operaciones

| Permiso | Admin | Vendedor | Cajero | Inventario | Cliente |
|---|:-:|:-:|:-:|:-:|:-:|
| `ver ventas` | ✅ | ✅ | ✅ | — | — |
| `crear ventas` | ✅ | ✅ | ✅ | — | — |
| `editar ventas` | ✅ | — | — | — | — |
| `cancelar ventas` | ✅ | — | — | — | — |
| `ver cotizaciones` | ✅ | ✅ | — | — | ✅ |
| `crear cotizaciones` | ✅ | ✅ | — | — | — |
| `editar cotizaciones` | ✅ | — | — | — | — |
| `eliminar cotizaciones` | ✅ | — | — | — | — |
| `ver numeros-serie` | ✅ | ✅ | — | ✅ | — |
| `editar numeros-serie` | ✅ | — | — | ✅ | — |
| `ver devoluciones` | ✅ | ✅ | — | — | — |
| `crear devoluciones` | ✅ | ✅ | — | — | — |
| `aprobar devoluciones` | ✅ | — | — | — | — |
| `rechazar devoluciones` | ✅ | — | — | — | — |
| `ver caja` | ✅ | ✅ | ✅ | — | — |
| `crear caja` | ✅ | ✅ | ✅ | — | — |
| `ver todos los cierres` | ✅ | — | — | — | — |
| `ver reportes` | ✅ | ✅ | — | ✅ | — |
| `exportar reportes` | ✅ | — | — | — | — |

### Compras

| Permiso | Admin | Vendedor | Cajero | Inventario | Cliente |
|---|:-:|:-:|:-:|:-:|:-:|
| `ver proveedores` | ✅ | — | — | ✅ | — |
| `crear proveedores` | ✅ | — | — | ✅ | — |
| `editar proveedores` | ✅ | — | — | ✅ | — |
| `eliminar proveedores` | ✅ | — | — | — | — |
| `ver ordenes-compra` | ✅ | — | — | ✅ | — |
| `crear ordenes-compra` | ✅ | — | — | ✅ | — |
| `editar ordenes-compra` | ✅ | — | — | ✅ | — |
| `recibir ordenes-compra` | ✅ | — | — | ✅ | — |

### Clientes y pedidos

| Permiso | Admin | Vendedor | Cajero | Inventario | Cliente |
|---|:-:|:-:|:-:|:-:|:-:|
| `ver clientes` | ✅ | ✅ | ✅ | ✅ | — |
| `crear clientes` | ✅ | ✅ | ✅ | — | — |
| `editar clientes` | ✅ | ✅ | ✅ | — | — |
| `eliminar clientes` | ✅ | — | — | — | — |
| `ver pedidos` | ✅ | ✅ | — | — | — |
| `confirmar pedidos` | ✅ | ✅ | — | — | — |
| `cancelar pedidos` | ✅ | — | — | — | — |

### Administración

| Permiso | Admin | Vendedor | Cajero | Inventario | Cliente |
|---|:-:|:-:|:-:|:-:|:-:|
| `ver usuarios` | ✅ | — | — | — | — |
| `crear usuarios` | ✅ | — | — | — | — |
| `editar usuarios` | ✅ | — | — | — | — |
| `eliminar usuarios` | ✅ | — | — | — | — |
| `ver respaldos` | ✅ | — | — | — | — |
| `crear respaldos` | ✅ | — | — | — | — |
| `ver encuestas` | ✅ | — | — | — | — |
| `ver auditoria` | ✅ | — | — | — | — |
| `ver facturacion` | ✅ | — | — | — | — |
| `emitir facturacion` | ✅ | — | — | — | — |
| `anular facturacion` | ✅ | — | — | — | — |

### Otros

| Permiso | Admin | Vendedor | Cajero | Inventario | Cliente |
|---|:-:|:-:|:-:|:-:|:-:|
| `usar compatibilidad` | ✅ | ✅ | — | ✅ | ✅ |
| `autorizar descuentos` | ✅ | — | — | — | — |

---

## 🛡️ Cómo se aplica la autorización

### 1. Gate::before (super-admin)

En `AppServiceProvider`:

```php
Gate::before(function ($user, $ability) {
    if ($user->hasRole('Admin')) {
        return true; // Admin puede TODO
    }
});
```

Esto significa que **Admin nunca necesita verificar permisos** — los tiene todos.

### 2. Policies (por recurso)

Cada modelo importante tiene una Policy:

```php
class ProductoPolicy
{
    public function viewAny(User $user): bool { return $user->can('ver productos'); }
    public function view(User $user, Producto $p): bool { return $user->can('ver productos'); }
    public function create(User $user): bool { return $user->can('crear productos'); }
    public function update(User $user, Producto $p): bool { return $user->can('editar productos'); }
    public function delete(User $user, Producto $p): bool { return $user->can('eliminar productos'); }
}
```

Registrada en `AppServiceProvider`:

```php
Gate::policy(Producto::class, ProductoPolicy::class);
```

### 3. Middleware en rutas

```php
Route::middleware(['auth', 'verified', 'user.active'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('productos', ProductoController::class);
    });
});
```

Y en el controlador:

```php
public function __construct()
{
    $this->authorizeResource(Producto::class, 'producto');
}
```

### 4. Directivas Blade

```blade
@can('crear productos')
    <a href="{{ route('admin.productos.create') }}">Nuevo producto</a>
@endcan
```

---

## 👤 Usuarios demo

| Rol | Email | Password |
|---|---|---|
| 👑 Admin | `admin@pixelstore.com` | `password` |
| 💼 Vendedor | `vendedor@pixelstore.com` | `password` |
| 📦 Inventario | `inventario@pixelstore.com` | `password` |
| 💰 Cajero | `cajero@pixelstore.com` | `password` |

---

## 🧪 Tests

El archivo `tests/Feature/RoleAccessTest.php` verifica la matriz:

```bash
php artisan test --filter=RoleAccessTest
```

**Tests actuales**:
- ✅ admin puede acceder al dashboard
- ✅ vendedor puede acceder al dashboard
- ✅ usuario desactivado no puede acceder
- ✅ admin tiene rol asignado
- ✅ vendedor no tiene rol admin

Además, la administración de usuarios y roles está cubierta por:

| Archivo | Casos | Qué verifica |
|---|:-:|---|
| `UserManagementTest` | 13 | CRUD de usuarios, roles múltiples, bloqueo sin rol |
| `UserPolicyTest` | 6 | Reglas de `UserPolicy` (auto-eliminación, último Admin) |
| `UserObserverTest` | 9 | Auditoría `created`/`updated`/`deleted` |
| `UserHistorialTest` | 3 | Historial de auditoría por usuario |
| `StoreUserRequestTest` / `UpdateUserRequestTest` | 6 + 6 | Validación de formularios |
| `AuditoriaLoginTest` | 4 | Login, logout e intentos fallidos en `logs_auditoria` |

### Verificar la matriz contra la BD

```bash
php artisan tinker --execute="foreach (Spatie\Permission\Models\Role::with('permissions')->orderBy('name')->get() as \$r) { echo \$r->name . ' (' . \$r->permissions->count() . ')' . PHP_EOL; }"
```

---

## 🔧 Cómo agregar un nuevo permiso

1. **Definir en `RoleSeeder`**:

```php
$permisos = [
    // ... permisos existentes
    'ver sucursales', 'crear sucursales',
];
```

2. **Asignar a roles**:

```php
$admin->syncPermissions(Permission::all());  // Admin ya lo tiene
$inventario->syncPermissions([
    'ver sucursales', 'crear sucursales',
]);
```

3. **Aplicar en Policy**:

```php
public function viewAny(User $user): bool
{
    return $user->can('ver sucursales');
}
```

4. **Aplicar en vistas**:

```blade
@can('ver sucursales')
    <x-nav-link ...>Sucursales</x-nav-link>
@endcan
```

5. **Correr seeder**:

```bash
php artisan db:seed --class=RoleSeeder
```

---

## ➡️ Siguiente paso

- [05 — Sprint 1](05-sprint-01.md) para ver qué se implementó
- [07 — Testing](07-testing.md) para ver cómo testear permisos
