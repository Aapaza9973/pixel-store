# 07 — Testing

Cómo correr, escribir y mantener los tests del proyecto.

---

## 🧪 Framework

| Herramienta | Versión |
|---|---|
| PHPUnit | 12.x |
| RefreshDatabase | `DatabaseTransactions` |
| Faker | Para factories |

---

## 🚀 Comandos básicos

```bash
# Correr TODA la suite
php artisan test

# Solo un archivo específico
php artisan test --filter=ProductoTest

# Solo un método
php artisan test --filter=test_admin_puede_crear_producto

# Con cobertura (requiere xdebug)
php artisan test --coverage

# En paralelo (más rápido)
php artisan test --parallel
```

---

## 📊 Estado actual

```
Tests:    96 passed (316 assertions)
Duration: ~22s
```

### Distribución por módulo

| Archivo | Tests | Cobertura |
|---|:-:|---|
| `Auth/AuthenticationTest` | 4 | Login, logout, credenciales |
| `Auth/RegistrationTest` | 2 | Registro |
| `Auth/PasswordResetTest` | 4 | Reset de contraseña |
| `Auth/PasswordConfirmationTest` | 3 | Confirmación |
| `Auth/PasswordUpdateTest` | 2 | Actualización |
| `Auth/EmailVerificationTest` | 3 | Verificación |
| `DashboardTest` | 2 | Métricas del dashboard |
| `InventoryServiceTest` | 14 | Servicio de inventario |
| `ProductoTest` | 7 | CRUD de productos |
| `ProductoFilterTest` | 10 | Filtros y búsqueda |
| `ProductoStockTest` | 14 | Stock por ubicación |
| `AlertaTest` | 8 | Sistema de alertas |
| `UbicacionTest` | 6 | CRUD de ubicaciones |
| `RoleAccessTest` | 5 | Matriz de permisos |
| `SessionExtendTest` | 5 | Extensión de sesión |
| `ProfileTest` | 5 | Gestión de perfil |
| `ExampleTest` | 2 | Smoke tests |

---

## 🏗️ Estructura de tests

```
tests/
├── Feature/                     # Tests de integración (HTTP)
│   ├── Auth/                    # Autenticación
│   ├── AlertaTest.php
│   ├── DashboardTest.php
│   ├── InventoryServiceTest.php
│   ├── ProductoFilterTest.php
│   ├── ProductoStockTest.php
│   ├── ProductoTest.php
│   ├── RoleAccessTest.php
│   ├── SessionExtendTest.php
│   └── UbicacionTest.php
├── Unit/                        # Tests de unidades aisladas
│   └── ExampleTest.php
└── TestCase.php                 # Clase base (carga schema)
```

---

## ⚙️ Configuración

### `phpunit.xml`

Configura la BD de tests:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="pgsql"/>
    <env name="DB_DATABASE" value="pixel_store_test"/>
    <env name="DB_USERNAME" value="postgres"/>
    <env name="DB_PASSWORD" value="PixelStore2026!"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
</php>
```

### `tests/TestCase.php`

Carga el schema en la BD de tests si no existe:

```php
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Carga pgsql-schema.sql si la BD de tests está vacía
        if ($this->app->environment('testing')) {
            $this->loadDatabaseSchemaIfEmpty();
        }
    }

    private function loadDatabaseSchemaIfEmpty(): void
    {
        // Verifica si existe la tabla 'users'
        // Si no, ejecuta el schema vía PDO paralelo
    }
}
```

---

## 📝 Cómo escribir un test

### Estructura básica

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MiNuevoTest extends TestCase
{
    use DatabaseTransactions;  // Rollback automático

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_algo_especifico(): void
    {
        // Arrange
        $admin = User::where('email', 'admin@pixelstore.com')->first();

        // Act
        $response = $this->actingAs($admin)->get('/dashboard');

        // Assert
        $response->assertOk();
        $response->assertSee('Dashboard');
    }
}
```

### Reglas

1. **Usar `DatabaseTransactions`** en lugar de `RefreshDatabase` (más rápido, no carga schema cada vez)
2. **Seedear en `setUp()`** solo los seeders necesarios
3. **Un test = una verificación** (idealmente)
4. **Nombres descriptivos**: `test_admin_puede_crear_producto` (no `testCreate`)
5. **No usar `dd()` ni `dump()`** en los tests

---

## 🎯 Tipos de tests

### 1. Feature tests (HTTP)

Prueban una petición HTTP end-to-end:

```php
public function test_admin_puede_crear_producto(): void
{
    $admin = User::where('email', 'admin@pixelstore.com')->first();

    $response = $this->actingAs($admin)->post(route('admin.productos.store'), [
        'nombre' => 'Producto Test',
        'categoria_id' => 1,
        'precio_unitario' => 100,
        'stock' => 10,
        'umbral_alerta' => 3,
    ]);

    $response->assertRedirect(route('admin.productos.index'));
    $this->assertDatabaseHas('productos', ['nombre' => 'Producto Test']);
}
```

### 2. Unit tests (aislados)

Prueban una clase o método específico:

```php
public function test_inventory_service_rechaza_stock_negativo(): void
{
    $this->expectException(StockInsuficienteException::class);

    $producto = Producto::factory()->create(['stock' => 5]);
    app(InventoryService::class)->ajustarStock($producto, -10, 'salida');
}
```

### 3. Tests de autorización

```php
public function test_vendedor_no_puede_crear_producto(): void
{
    $vendedor = User::where('email', 'vendedor@pixelstore.com')->first();

    $response = $this->actingAs($vendedor)->post(route('admin.productos.store'), [...]);

    $response->assertForbidden();  // 403
}
```

---

## 🐛 Debugging

### Ver la respuesta completa

```php
$response->dump();
$response->dumpHeaders();
$response->dumpSession();
```

### Detener en un punto

```php
$this->withoutExceptionHandling();  // Muestra excepciones reales
```

### Ver la sesión

```php
$response->assertSessionHas('success');
$response->assertSessionHasErrors('nombre');
```

---

## 📋 Checklist antes de commitear

- [ ] `php artisan test` pasa todo
- [ ] `./vendor/bin/pint --dirty` sin errores
- [ ] Los tests nuevos cubren el caso de éxito
- [ ] Los tests nuevos cubren el caso de error (403, validación)
- [ ] Sin `dd()`, `dump()`, `var_dump()` en el código
- [ ] Sin tests comentados

---

## 🎯 Comandos útiles

```bash
# Tests en paralelo (más rápido)
php artisan test --parallel

# Detener al primer fallo
php artisan test --stop-on-failure

# Solo tests que fallaron la última vez
php artisan test --only-failures

# Con cobertura HTML
php artisan test --coverage-html coverage/

# Filtrar por nombre de grupo
php artisan test --group=slow
```

---

## ➡️ Siguiente paso

- [05 — Sprint 1](05-sprint-01.md) para ver qué se ha testeado
- [08 — Despliegue](08-despliegue.md) para ver cómo desplegar con confianza
